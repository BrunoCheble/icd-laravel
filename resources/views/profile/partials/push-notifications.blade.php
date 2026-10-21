{{-- The app's notifications on this device (Web Push): turned on (the browser asks), off, and a test. --}}
<section x-data="pushNotifications(@js([
        'key' => app(\App\Support\WebPush::class)->publicKey(),
        'subscribe' => route('push.subscribe'),
        'unsubscribe' => route('push.unsubscribe'),
        'test' => route('push.test'),
    ]))">
    <header>
        <h2 class="text-lg font-medium text-gray-900"><i class="fa-solid fa-bell" style="color: var(--brand);"></i> {{ __('Notifications') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Receive on this device, also with the app closed: the birthdays and wedding anniversaries of the day in the morning, and every hour what is new (prayer requests, announcements to approve, comments, members who registered). Not at night.') }}</p>
    </header>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <template x-if="state === 'off'">
            <x-primary-button type="button" @click="turnOn()" ::disabled="busy"><i class="fa-solid fa-bell me-2"></i> {{ __('Turn on on this device') }}</x-primary-button>
        </template>
        <template x-if="state === 'on'">
            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button type="button" @click="test()" ::disabled="busy"><i class="fa-solid fa-paper-plane me-2"></i> {{ __('Send a test') }}</x-primary-button>
                <x-secondary-button type="button" @click="turnOff()" ::disabled="busy">{{ __('Turn off') }}</x-secondary-button>
            </div>
        </template>
        <p class="text-sm text-gray-600" x-show="state === 'on'"><i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> {{ __('On on this device.') }}</p>
        <p class="text-sm text-gray-600" x-show="state === 'off'">{{ __('Off on this device.') }}</p>
        <p class="text-sm text-gray-600" x-show="state === 'denied'">{{ __('Notifications are blocked for this site: allow them in the browser\'s or the phone\'s settings.') }}</p>
        <p class="text-sm text-gray-600" x-show="state === 'unsupported'">{{ __('This browser does not receive notifications. On an iPhone, add the app to the home screen first (Share → Add to Home Screen) and open it from there.') }}</p>
    </div>
    <p class="mt-2 text-sm" x-show="message" x-text="message"></p>

    <script>
        function pushNotifications({ key, subscribe, unsubscribe, test }) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const call = (url, method, body) => fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
            const bytes = (text) => Uint8Array.from(atob((text + '='.repeat((4 - text.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/')), (c) => c.charCodeAt(0));
            return {
                state: 'off', busy: false, message: '',
                async init() {
                    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) { this.state = 'unsupported'; return; }
                    if (Notification.permission === 'denied') { this.state = 'denied'; return; }
                    const registration = await navigator.serviceWorker.ready;
                    this.state = (await registration.pushManager.getSubscription()) ? 'on' : 'off';
                },
                async turnOn() {
                    this.busy = true; this.message = '';
                    try {
                        if (await Notification.requestPermission() !== 'granted') { this.state = Notification.permission === 'denied' ? 'denied' : 'off'; return; }
                        const registration = await navigator.serviceWorker.ready;
                        const subscription = (await registration.pushManager.getSubscription())
                            || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: bytes(key) });
                        if (!(await call(subscribe, 'POST', subscription.toJSON())).ok) throw new Error();
                        this.state = 'on';
                        await this.test();
                    } catch (e) { this.message = @js(__('It was not possible now. Try again.')); }
                    finally { this.busy = false; }
                },
                async turnOff() {
                    this.busy = true; this.message = '';
                    try {
                        const subscription = await (await navigator.serviceWorker.ready).pushManager.getSubscription();
                        if (subscription) { await call(unsubscribe, 'DELETE', { endpoint: subscription.endpoint }); await subscription.unsubscribe(); }
                        this.state = 'off';
                    } catch (e) { this.message = @js(__('It was not possible now. Try again.')); }
                    finally { this.busy = false; }
                },
                async test() {
                    const subscription = await (await navigator.serviceWorker.ready).pushManager.getSubscription();
                    const response = subscription && await call(test, 'POST', { endpoint: subscription.endpoint });
                    this.message = response?.ok ? @js(__('Test sent: it should arrive in a few seconds.')) : @js(__('It was not possible now. Try again.'));
                },
            };
        }
    </script>
</section>
