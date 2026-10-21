<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Sign in') }} · ICD Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.pwa')
    @include('layouts.partials.theme')
    <style>
        [x-cloak] { display: none !important; }
        body { margin: 0; background: var(--bg-f4f5f7); }
        .login { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 900px) { .login { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); } }

        /* The church's side: its colors and logo (large screens) */
        .login-brand { display: none; position: relative; overflow: hidden; padding: 3rem; color: #fff; background: radial-gradient(120% 90% at 0% 0%, #fbbf24 0%, #f8a925 35%, #d8881c 70%, #b45309 100%); }
        @media (min-width: 900px) { .login-brand { display: flex; flex-direction: column; justify-content: space-between; } }
        .login-brand::after { content: ''; position: absolute; right: -18%; bottom: -22%; width: 70%; aspect-ratio: 1; border-radius: 50%; background: rgba(255, 255, 255, .12); }
        .login-brand .mark { display: flex; align-items: center; gap: .9rem; font-size: 1.25rem; font-weight: 800; }
        .login-brand .mark img { width: 3.25rem; height: 3.25rem; padding: .35rem; border-radius: 1rem; background: #fff; box-shadow: 0 6px 20px rgba(0, 0, 0, .15); }
        .login-brand h1 { position: relative; z-index: 1; margin: 0; max-width: 26rem; font-size: 2.6rem; line-height: 1.1; font-weight: 800; letter-spacing: -.02em; }
        .login-brand p { position: relative; z-index: 1; margin: .9rem 0 0; max-width: 24rem; font-size: 1.05rem; opacity: .92; }
        .login-brand small { position: relative; z-index: 1; opacity: .8; }

        /* The form */
        .login-main { display: flex; align-items: center; justify-content: center; padding: 2rem 1.25rem; }
        .login-card { width: min(24rem, 100%); }
        .login-logo { display: block; width: 4.5rem; height: 4.5rem; margin: 0 auto 1.25rem; padding: .45rem; border-radius: 1.25rem; background: var(--bg-fff); box-shadow: 0 6px 20px rgba(0, 0, 0, .08); }
        @media (min-width: 900px) { .login-logo { display: none; } }
        .login-card h2 { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--tx-111827); letter-spacing: -.01em; }
        .login-card .lead { margin: .35rem 0 1.75rem; color: var(--tx-6b7280); }
        .field { margin-bottom: 1rem; }
        .field label { display: block; margin-bottom: .35rem; font-size: .85rem; font-weight: 700; color: var(--tx-374151); }
        .input { position: relative; }
        .input > i { position: absolute; left: .95rem; top: 50%; transform: translateY(-50%); color: var(--tx-9ca3af); pointer-events: none; }
        .input input { width: 100%; min-height: 50px; padding: 0 2.9rem 0 2.75rem; border: 1px solid var(--bd-d1d5db); border-radius: .8rem; background-color: var(--bg-fff); font-size: 16px; color: var(--tx-111827); }
        .input input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px var(--brand-100); }
        .input .reveal { position: absolute; right: .35rem; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border: 0; border-radius: .6rem; background: none; color: var(--tx-9ca3af); cursor: pointer; }
        .input .reveal:hover { color: var(--tx-374151); background: var(--bg-f3f4f6); }
        .not-you { margin-top: .35rem; border: 0; background: none; padding: 0; font-size: .8rem; color: var(--tx-6b7280); text-decoration: underline; cursor: pointer; }
        .error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: var(--tx-dc2626); }
        .status { margin-bottom: 1rem; padding: .7rem .9rem; border-radius: .7rem; background: var(--bg-dcfce7); color: var(--tx-166534); font-size: .9rem; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: .25rem 0 1.25rem; }
        .remember { display: inline-flex; align-items: center; gap: .5rem; font-size: .9rem; color: var(--tx-4b5563); cursor: pointer; }
        .remember input { width: 1.1rem; height: 1.1rem; border-radius: .3rem; border-color: var(--bd-d1d5db); color: #f8a925; }
        .btn-primary { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 52px; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font-size: 1.05rem; font-weight: 800; cursor: pointer; box-shadow: 0 8px 20px rgba(248, 169, 37, .3); }
        .btn-primary:hover { background: #d8881c; }
        .divider { display: flex; align-items: center; gap: .75rem; margin: 1.5rem 0; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--tx-9ca3af); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--bg-e5e7eb); }
        .btn-passkey { display: flex; align-items: center; justify-content: center; gap: .65rem; width: 100%; min-height: 52px; border: 0; border-radius: .9rem; background: #111827; color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .btn-passkey i { font-size: 1.35rem; color: #fbbf24; }
        .btn-passkey:disabled { opacity: .65; }
        .hint { margin: .6rem 0 0; text-align: center; font-size: .8rem; color: var(--tx-9ca3af); }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="login">
        <aside class="login-brand">
            <div class="mark"><img src="{{ asset('icons/admin-icon-192.png') }}" alt=""> ICD Admin</div>
            <div>
                <h1>{{ __('Welcome back.') }}</h1>
                <p>{{ __('Members, announcements, songs and setlists of the Igreja Cidade de Davi, in one place.') }}</p>
            </div>
            <small>Igreja Cidade de Davi</small>
        </aside>

        <main class="login-main">
            <div class="login-card" x-data="loginPage(@js([
                    'options' => route('passkeys.sign-in.options'),
                    'signIn' => route('passkeys.sign-in'),
                    'texts' => [
                        'cancelled' => __('Cancelled on the device. Try again.'),
                        'already' => __('This device is already registered.'),
                        'failed' => __('The fingerprint could not be checked. Try again.'),
                    ],
                ]))">
                <img class="login-logo" src="{{ asset('icons/admin-icon-192.png') }}" alt="">
                <h2>{{ __('Sign in') }}</h2>
                <p class="lead">{{ __('Use your email and password, or your fingerprint / Face ID.') }}</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" @submit="remember()">
                    @csrf
                    <div class="field">
                        <label for="email">{{ __('Email') }}</label>
                        <div class="input">
                            <i class="fa-regular fa-envelope"></i>
                            <input id="email" type="email" name="email" x-ref="email" value="{{ old('email') }}" required autocomplete="username" inputmode="email" placeholder="nome@exemplo.com">
                        </div>
                        <button type="button" class="not-you" x-show="savedEmail" x-cloak @click="forget()">{{ __('Not you? Use another email') }}</button>
                        @error('email')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="password">{{ __('Password') }}</label>
                        <div class="input">
                            <i class="fa-solid fa-lock"></i>
                            <input id="password" :type="showPassword ? 'text' : 'password'" type="password" name="password" x-ref="password" required autocomplete="current-password">
                            <button type="button" class="reveal" @click="showPassword = !showPassword" :aria-label="showPassword ? @js(__('Hide the password')) : @js(__('Show the password'))">
                                <i class="fa-regular" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div class="row">
                        <label class="remember"><input type="checkbox" name="remember" value="1" checked> {{ __('Keep me signed in') }}</label>
                    </div>

                    <button type="submit" class="btn-primary"><i class="fa-solid fa-arrow-right-to-bracket"></i> {{ __('Sign in') }}</button>
                </form>

                {{-- Fingerprint / Face ID: a passkey registered on the profile of this device --}}
                <div x-show="supported" x-cloak>
                    <div class="divider">{{ __('or') }}</div>
                    <button type="button" class="btn-passkey" @click="passkey()" :disabled="busy">
                        <i class="fa-solid fa-fingerprint"></i>
                        <span x-text="busy ? @js(__('Waiting for the device…')) : @js(__('Sign in with fingerprint / Face ID'))"></span>
                    </button>
                    <p class="error" style="text-align: center;" x-show="error" x-text="error"></p>
                    <p class="hint">{{ __('First turn it on in your profile, on this device.') }}</p>
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('js/passkeys.js') }}?v={{ filemtime(public_path('js/passkeys.js')) }}"></script>
    <script>
        // The sign-in page: the email used last on this device comes filled in (kept only on this device), the
        // password can be shown, and the fingerprint / Face ID signs in without either.
        function loginPage({ options, signIn, texts }) {
            const KEY = 'login-email';
            let saved = '';
            try { saved = localStorage.getItem(KEY) || ''; } catch (error) {}
            return {
                supported: window.Passkeys && Passkeys.supported(),
                savedEmail: saved,
                showPassword: false,
                busy: false,
                error: '',
                init() {
                    if (saved && !this.$refs.email.value) {
                        this.$refs.email.value = saved;
                        this.$nextTick(() => this.$refs.password.focus());
                    } else if (!this.$refs.email.value) {
                        this.$nextTick(() => this.$refs.email.focus());
                    }
                },
                remember() {
                    try { localStorage.setItem(KEY, this.$refs.email.value.trim()); } catch (error) {}
                },
                forget() {
                    try { localStorage.removeItem(KEY); } catch (error) {}
                    this.savedEmail = '';
                    this.$refs.email.value = '';
                    this.$refs.email.focus();
                },
                async passkey() {
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
</body>
</html>
