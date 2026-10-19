{{-- The app's lock over the page (only for users with a passkey, see SessionLock): after a while without use, or when
     coming back to the app after a while away, the page is covered until unlocked; it stays as it was underneath. While
     in use, the page tells the server now and then, so a page being worked on does not lock. --}}
<div class="app-lock" x-data="appLock(@js(['ping' => route('lock.ping'), 'idleMinutes' => \App\Services\Passkeys\SessionLock::IDLE_MINUTES]))"
    x-show="locked" x-cloak @app-unlocked.window="unlocked()" role="dialog" aria-modal="true" aria-label="{{ __('ICD Admin locked') }}">
    @include('layouts.partials.lock-panel', ['overlay' => true])
</div>

@once
    <style>
        .app-lock { position: fixed; inset: 0; z-index: 200; display: grid; place-items: center; padding: 1.5rem; background: rgba(244, 245, 247, .97); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
    </style>
    <script>
        function appLock({ ping, idleMinutes }) {
            const idle = idleMinutes * 60 * 1000;
            const PING_EVERY = 2 * 60 * 1000;
            return {
                locked: false,
                lastUse: Date.now(),
                lastPing: Date.now(),
                hiddenAt: null,
                init() {
                    const used = () => {
                        if (this.locked) return;
                        this.lastUse = Date.now();
                        this.tellServer();
                    };
                    ['pointerdown', 'keydown', 'wheel', 'touchstart', 'scroll'].forEach((type) => window.addEventListener(type, used, { passive: true, capture: true }));
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) { this.hiddenAt = Date.now(); return; }
                        if (this.hiddenAt !== null && Date.now() - this.hiddenAt > idle) this.locked = true;
                        this.hiddenAt = null;
                        this.check();
                    });
                    setInterval(() => this.check(), 15000);
                },
                check() {
                    if (!this.locked && Date.now() - this.lastUse > idle) this.locked = true;
                },
                // In use: the server's clock is set again (at most every 2 minutes); a session it locked locks the page.
                async tellServer() {
                    if (Date.now() - this.lastPing < PING_EVERY) return;
                    this.lastPing = Date.now();
                    try {
                        const response = await fetch(ping, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                        if (response.status === 423) this.locked = true;
                    } catch (error) {}
                },
                unlocked() {
                    this.locked = false;
                    this.lastUse = Date.now();
                    this.lastPing = Date.now();
                },
            };
        }
    </script>
@endonce
