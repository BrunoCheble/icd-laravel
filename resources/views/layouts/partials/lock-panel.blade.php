{{-- Unlocking the app (see App\Services\Passkeys\SessionLock): fingerprint / Face ID, or the password, or signing out.
     $overlay: shown over the page (it stays as it was) instead of on the lock screen (which then goes back to it). --}}
<div class="lock-panel" x-data="lockPanel(@js([
        'options' => route('lock.options'),
        'unlock' => route('lock.unlock'),
        'password' => route('lock.password'),
        'overlay' => $overlay ?? false,
        'texts' => [
            'cancelled' => __('Cancelled on the device. Try again.'),
            'already' => __('This device is already registered.'),
            'failed' => __('The fingerprint could not be checked. Try again.'),
        ],
    ]))">
    <img src="{{ asset('icons/admin-icon-192.png') }}" alt="" width="72" height="72" class="lock-logo">
    <h2 class="lock-title"><i class="fa-solid fa-lock"></i> {{ __('ICD Admin locked') }}</h2>
    <p class="lock-text">{{ __('Hello, :name. Use your fingerprint or Face ID to continue.', ['name' => Auth::user()->name]) }}</p>

    <button type="button" class="lock-primary" @click="unlock()" :disabled="busy">
        <i class="fa-solid fa-fingerprint"></i> <span x-text="busy ? @js(__('Waiting for the device…')) : @js(__('Unlock'))"></span>
    </button>
    <p class="lock-error" x-show="error" x-text="error" x-cloak></p>

    <button type="button" class="lock-link" x-show="!usePassword" @click="usePassword = true; $nextTick(() => $refs.password.focus())">{{ __('Use the password') }}</button>
    <form class="lock-password" x-show="usePassword" x-cloak @submit.prevent="unlockWithPassword()">
        <input type="password" x-ref="password" x-model="password" autocomplete="current-password" placeholder="{{ __('Password') }}" aria-label="{{ __('Password') }}">
        <button type="submit" :disabled="busy || !password">{{ __('Unlock') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="lock-logout">
        @csrf
        <button type="submit" class="lock-link"><i class="fa-solid fa-arrow-right-from-bracket"></i> {{ __('Log Out') }}</button>
    </form>
</div>

@once
    <script src="{{ asset('js/passkeys.js') }}?v={{ filemtime(public_path('js/passkeys.js')) }}"></script>
    <style>
        .lock-panel { width: min(22rem, 100%); margin: 0 auto; text-align: center; }
        .lock-logo { margin: 0 auto 1rem; border-radius: 1.25rem; box-shadow: 0 4px 16px rgba(0, 0, 0, .12); }
        .lock-title { margin: 0; font-size: 1.25rem; font-weight: 800; color: #111827; }
        .lock-title i { margin-right: .3rem; font-size: .9em; color: var(--brand-hover, #d8881c); }
        .lock-text { margin: .5rem 0 1.5rem; color: #6b7280; font-size: .95rem; }
        .lock-primary { display: inline-flex; align-items: center; justify-content: center; gap: .6rem; width: 100%; min-height: 52px; border: 0; border-radius: .9rem; background: var(--brand, #f8a925); color: #fff; font-size: 1.05rem; font-weight: 800; cursor: pointer; }
        .lock-primary i { font-size: 1.4rem; }
        .lock-primary:disabled { opacity: .7; }
        .lock-error { margin: .75rem 0 0; color: #dc2626; font-size: .875rem; font-weight: 600; }
        .lock-link { margin-top: 1rem; border: 0; background: none; color: #6b7280; font-size: .9rem; font-weight: 600; text-decoration: underline; cursor: pointer; }
        .lock-password { display: flex; gap: .5rem; margin-top: 1rem; }
        .lock-password input { flex: 1; min-width: 0; min-height: 46px; padding: 0 .8rem; border: 1px solid #d1d5db; border-radius: .7rem; font-size: 16px; }
        .lock-password button { min-height: 46px; padding: 0 1rem; border: 0; border-radius: .7rem; background: #111827; color: #fff; font-weight: 700; }
        .lock-logout { margin-top: .5rem; }
    </style>
    <script>
        // Unlocking: with a passkey of this device for the signed-in user, or the password. Over a page, the page stays
        // as it was (unsaved changes too); on the lock screen, it goes back to the page that was asked for.
        function lockPanel({ options, unlock, password, overlay, texts }) {
            return {
                busy: false,
                error: '',
                usePassword: false,
                password: '',
                done(result) {
                    this.password = '';
                    this.error = '';
                    if (overlay) window.dispatchEvent(new CustomEvent('app-unlocked'));
                    else location.href = result.redirect;
                },
                failed(error) {
                    // The session ended meanwhile: the page itself shows the way back in.
                    if (error && (error.status === 419 || error.status === 401)) { location.reload(); return; }
                    this.error = Passkeys.message(error, texts);
                },
                async unlock() {
                    if (!Passkeys.supported()) { this.usePassword = true; return; }
                    this.busy = true;
                    this.error = '';
                    try { this.done(await Passkeys.signIn(options, unlock)); } catch (error) { this.failed(error); } finally { this.busy = false; }
                },
                async unlockWithPassword() {
                    this.busy = true;
                    this.error = '';
                    try { this.done(await Passkeys.post(password, { password: this.password })); } catch (error) { this.failed(error); } finally { this.busy = false; }
                },
            };
        }
    </script>
@endonce
