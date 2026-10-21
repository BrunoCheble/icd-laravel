<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Choose your password') }} · ICD Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.pwa')
    @include('layouts.partials.theme')
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: var(--bg-f4f5f7); }
        .pw-card { width: min(24rem, 100%); padding: 1.75rem 1.5rem; border: 1px solid var(--bd-e5e7eb); border-radius: 1.25rem; background: var(--bg-fff); box-shadow: 0 12px 30px rgba(16, 24, 40, .08); }
        .pw-card img { display: block; width: 3.75rem; height: 3.75rem; margin: 0 auto 1rem; padding: .4rem; border-radius: 1rem; background: var(--bg-fff); box-shadow: 0 6px 20px rgba(0, 0, 0, .08); }
        .pw-card h1 { margin: 0; text-align: center; font-size: 1.4rem; font-weight: 800; color: var(--tx-111827); }
        .pw-card .lead { margin: .4rem 0 1.5rem; text-align: center; color: var(--tx-6b7280); font-size: .95rem; }
        .field { margin-bottom: 1rem; }
        .field label { display: block; margin-bottom: .35rem; font-size: .85rem; font-weight: 700; color: var(--tx-374151); }
        .input { position: relative; }
        .input > i { position: absolute; left: .95rem; top: 50%; transform: translateY(-50%); color: var(--tx-9ca3af); pointer-events: none; }
        .input input { width: 100%; min-height: 50px; padding: 0 2.9rem 0 2.75rem; border: 1px solid var(--bd-d1d5db); border-radius: .8rem; background-color: var(--bg-fff); font-size: 16px; color: var(--tx-111827); }
        .input input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px var(--brand-100); }
        .input .reveal { position: absolute; right: .35rem; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border: 0; border-radius: .6rem; background: none; color: var(--tx-9ca3af); cursor: pointer; }
        .hint { margin: .35rem 0 0; font-size: .8rem; color: var(--tx-6b7280); }
        .error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: var(--tx-dc2626); }
        .btn-primary { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 52px; margin-top: 1.25rem; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font-size: 1.05rem; font-weight: 800; cursor: pointer; }
        .btn-primary:hover { background: #d8881c; }
        .logout { margin-top: 1rem; text-align: center; }
        .logout button { border: 0; background: none; font-size: .85rem; color: var(--tx-6b7280); text-decoration: underline; cursor: pointer; }
    </style>
</head>
<body class="font-sans antialiased">
    {{-- The first sign-in with a temporary password: a password of one's own before anything else --}}
    <main class="pw-card" x-data="{ show: false }">
        <img src="{{ asset('icons/admin-icon-192.png') }}" alt="">
        <h1>{{ __('Choose your password') }}</h1>
        <p class="lead">{{ __('Hello, :name! You signed in with a temporary password: now choose your own.', ['name' => explode(' ', trim($user->name))[0]]) }}</p>

        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="password">{{ __('New password') }}</label>
                <div class="input">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required minlength="8" autocomplete="new-password" autofocus>
                    <button type="button" class="reveal" @click="show = !show" :aria-label="show ? @js(__('Hide the password')) : @js(__('Show the password'))"><i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button>
                </div>
                <p class="hint">{{ __('At least 8 characters.') }}</p>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                <div class="input">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" type="password" required minlength="8" autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> {{ __('Save password') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="logout">
            @csrf
            <button type="submit">{{ __('Log Out') }}</button>
        </form>
    </main>
</body>
</html>
