{{-- The app's notifications on this device (Web Push): turned on (the browser asks), off, and a test. --}}
<section x-data="pushNotifications(@js([
        'key' => app(\App\Support\WebPush::class)->publicKey(),
        'subscribe' => route('push.subscribe'),
        'unsubscribe' => route('push.unsubscribe'),
        'test' => route('push.test'),
        'preferences' => route('push.preferences'),
        'kinds' => \App\Services\Notifications\SendPushDigestService::kindsOf(auth()->user()),
    ]))">
    <header>
        <h2 class="text-lg font-medium text-gray-900"><i class="fa-solid fa-bell" style="color: var(--brand);"></i> {{ __('Notifications') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Receive on this device, also with the app closed: the birthdays and wedding anniversaries of the day in the morning, and what is new as soon as it comes in. Not at night: it waits for the morning. Choose below what you want to receive.') }}</p>
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

    {{-- The kinds wanted (on all of this user's devices) --}}
    <fieldset class="mt-5" @if (! \App\Services\Notifications\SendPushDigestService::allowedKinds(auth()->user())) hidden @endif>
        <legend class="text-sm font-medium text-gray-900">{{ __('What to receive') }}</legend>
        <div class="mt-2 grid gap-2">
            {{-- Only the kinds of the modules the user may see --}}
            @foreach (array_intersect_key(\App\Services\Notifications\SendPushDigestService::KINDS, array_flip(\App\Services\Notifications\SendPushDigestService::allowedKinds(auth()->user()))) as $kind => $label)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" value="{{ $kind }}" x-model="kinds" @change="savePreferences()" class="rounded border-gray-300" style="color: var(--brand);">
                    {{ __($label) }}
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-500" x-show="savedKinds" x-text="savedKinds"></p>
    </fieldset>

    <script>
        function pushNotifications({ key, subscribe, unsubscribe, test, preferences, kinds }) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const call = (url, method, body) => fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
            // A failed step says which one, so it can be told apart (permission, subscription, saving, test).
            const failed = (step, error) => `${@js(__('It was not possible now. Try again.'))} (${step}: ${error?.message || error?.name || error})`;
            const bytes = (text) => Uint8Array.from(atob((text + '='.repeat((4 - text.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/')), (c) => c.charCodeAt(0));
            return {
                state: 'off', busy: false, message: '', kinds, savedKinds: '', savedTimer: null,
                // Saved on each change (a moment later).
                savePreferences() {
                    clearTimeout(this.savedTimer);
                    this.savedTimer = setTimeout(async () => {
                        const response = await call(preferences, 'PATCH', { kinds: this.kinds }).catch(() => null);
                        this.savedKinds = response?.ok ? @js(__('Saved.')) : @js(__('It was not possible now. Try again.'));
                    }, 400);
                },
                async init() {
                    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) { this.state = 'unsupported'; return; }
                    if (Notification.permission === 'denied') { this.state = 'denied'; return; }
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    this.state = subscription ? 'on' : 'off';
                    // On this device: told to the system again (it may not have it, e.g. after a failed saving).
                    if (subscription) call(subscribe, 'POST', subscription.toJSON()).catch(() => {});
                },
                async turnOn() {
                    this.busy = true; this.message = '';
                    let step = 'permissão';
                    try {
                        if (await Notification.requestPermission() !== 'granted') { this.state = Notification.permission === 'denied' ? 'denied' : 'off'; return; }
                        step = 'service worker';
                        const registration = await Promise.race([navigator.serviceWorker.ready, new Promise((resolve, reject) => setTimeout(() => reject(new Error('sem service worker ativo')), 8000))]);
                        step = 'inscrição';
                        let subscription = await registration.pushManager.getSubscription();
                        // One made with another server key is renewed.
                        if (subscription && subscription.options?.applicationServerKey && btoa(String.fromCharCode(...new Uint8Array(subscription.options.applicationServerKey))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '') !== key) {
                            await subscription.unsubscribe();
                            subscription = null;
                        }
                        subscription = subscription || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: bytes(key) });
                        step = 'gravar';
                        const saved = await call(subscribe, 'POST', subscription.toJSON());
                        if (!saved.ok) throw new Error('HTTP ' + saved.status);
                        this.state = 'on';
                        await this.test();
                    } catch (e) { this.message = failed(step, e); }
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
                    const answer = response ? await response.json().catch(() => ({})) : {};
                    this.message = response?.ok ? @js(__('Test sent: it should arrive in a few seconds.')) : failed('teste', answer.error || ('HTTP ' + (response?.status ?? '-')));
                },
            };
        }
    </script>
</section>
