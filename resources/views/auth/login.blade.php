<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary-700 shadow-sm focus:ring-primary-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    {{-- Fingerprint / Face ID: a passkey registered on the profile of this device --}}
    <div class="mt-6" x-data="passkeySignIn(@js([
            'options' => route('passkeys.sign-in.options'),
            'signIn' => route('passkeys.sign-in'),
            'texts' => [
                'cancelled' => __('Cancelled on the device. Try again.'),
                'already' => __('This device is already registered.'),
                'failed' => __('The fingerprint could not be checked. Try again.'),
            ],
        ]))" x-show="supported" x-cloak>
        <div class="flex items-center gap-3 text-xs uppercase tracking-wide text-gray-400"><span class="h-px flex-1 bg-gray-200"></span>{{ __('or') }}<span class="h-px flex-1 bg-gray-200"></span></div>
        <button type="button" @click="go()" :disabled="busy"
            class="mt-4 flex w-full items-center justify-center gap-3 rounded-xl bg-gray-900 px-4 py-3 text-base font-bold text-white hover:bg-gray-800 disabled:opacity-60">
            <i class="fa-solid fa-fingerprint text-xl"></i>
            <span x-text="busy ? @js(__('Waiting for the device…')) : @js(__('Sign in with fingerprint / Face ID'))"></span>
        </button>
        <p class="mt-3 text-center text-sm font-semibold text-red-600" x-show="error" x-text="error"></p>
        <p class="mt-2 text-center text-xs text-gray-500">{{ __('First turn it on in your profile, on this device.') }}</p>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak] { display: none !important; }</style>
    <script src="{{ asset('js/passkeys.js') }}?v={{ filemtime(public_path('js/passkeys.js')) }}"></script>
    <script>
        function passkeySignIn({ options, signIn, texts }) {
            return {
                supported: window.Passkeys && Passkeys.supported(),
                busy: false,
                error: '',
                async go() {
                    this.busy = true;
                    this.error = '';
                    try {
                        const result = await Passkeys.signIn(options, signIn);
                        location.href = result.redirect;
                    } catch (error) {
                        this.error = Passkeys.message(error, texts);
                        this.busy = false;
                    }
                },
            };
        }
    </script>
</x-guest-layout>
