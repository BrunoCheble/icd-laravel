{{-- Signing in with fingerprint / Face ID: the devices registered, and registering this one (after the password) --}}
<section x-data="passkeyDevices(@js([
        'options' => route('passkeys.options'),
        'store' => route('passkeys.store'),
        'texts' => [
            'cancelled' => __('Cancelled on the device. Try again.'),
            'already' => __('This device is already registered.'),
            'failed' => __('The fingerprint could not be checked. Try again.'),
        ],
    ]))">
    <header>
        <h2 class="text-lg font-medium text-gray-900"><i class="fa-solid fa-fingerprint" style="color: var(--brand-hover);"></i> {{ __('Fingerprint / Face ID') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Sign in on this phone with your fingerprint or Face ID, without typing the password. The app also asks for it when you come back after a while away.') }}</p>
    </header>

    @php $passkeys = Auth::user()->passkeys()->latest()->get(); @endphp
    @if (session('status') === 'passkey-removed')
        <p class="mt-3 text-sm font-semibold text-green-700">{{ __('Device removed.') }}</p>
    @endif

    <ul class="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-200" @if ($passkeys->isEmpty()) style="display: none;" @endif>
        @foreach ($passkeys as $passkey)
            <li class="flex items-center gap-3 px-3 py-2">
                <i class="fa-solid fa-mobile-screen-button text-gray-400"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $passkey->name }}</p>
                    <p class="text-xs text-gray-500">{{ __('Added on :date', ['date' => $passkey->created_at->format('d/m/Y')]) }}@if ($passkey->last_used_at) · {{ __('last used on :date', ['date' => $passkey->last_used_at->format('d/m/Y H:i')]) }}@endif</p>
                </div>
                <form method="POST" action="{{ route('passkeys.destroy', $passkey) }}" data-confirm="{{ __('Remove this device? It will no longer sign in with the fingerprint.') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md px-2 py-1 text-sm font-semibold text-red-600 hover:bg-red-50"><i class="fa-solid fa-trash"></i> {{ __('Remove') }}</button>
                </form>
            </li>
        @endforeach
    </ul>

    <p class="mt-4 text-sm text-gray-600" x-show="!supported" x-cloak>{{ __('This browser cannot use the fingerprint here. Open the admin on the phone (Safari on iPhone, Chrome on Android), over HTTPS.') }}</p>

    <form class="mt-4 space-y-3" x-show="supported" @submit.prevent="add()">
        <div>
            <x-input-label for="passkey_name" :value="__('Name of this device')" />
            <x-text-input id="passkey_name" type="text" class="mt-1 block w-full" x-model="name" maxlength="100" required />
        </div>
        <div>
            <x-input-label for="passkey_password" :value="__('Current Password')" />
            <x-text-input id="passkey_password" type="password" class="mt-1 block w-full" x-model="password" autocomplete="current-password" required />
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button x-bind:disabled="busy"><i class="fa-solid fa-fingerprint me-2"></i> <span x-text="busy ? @js(__('Waiting for the device…')) : @js(__('Use the fingerprint on this device'))"></span></x-primary-button>
            <span class="text-sm font-semibold" :class="ok ? 'text-green-700' : 'text-red-600'" x-show="message" x-text="message"></span>
        </div>
    </form>
</section>

@once
    <script src="{{ asset('js/passkeys.js') }}?v={{ filemtime(public_path('js/passkeys.js')) }}"></script>
    <script>
        function passkeyDevices({ options, store, texts }) {
            const ua = navigator.userAgent;
            const guess = /iPhone/.test(ua) ? 'iPhone' : /iPad/.test(ua) ? 'iPad' : /Android/.test(ua) ? 'Android' : /Mac/.test(ua) ? 'Mac' : /Windows/.test(ua) ? 'Windows' : '';
            return {
                supported: window.Passkeys && Passkeys.supported(),
                name: guess,
                password: '',
                busy: false,
                message: '',
                ok: false,
                async add() {
                    this.busy = true;
                    this.message = '';
                    try {
                        await Passkeys.register(options, store, this.password, this.name.trim() || guess || 'Device');
                        this.ok = true;
                        this.message = @js(__('Done: you can sign in with the fingerprint on this device.'));
                        setTimeout(() => location.reload(), 1200);
                    } catch (error) {
                        this.ok = false;
                        this.message = Passkeys.message(error, texts);
                    } finally {
                        this.password = '';
                        this.busy = false;
                    }
                },
            };
        }
    </script>
@endonce
