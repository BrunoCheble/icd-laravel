{{-- Light or dark (see resources/css/app.css): the choice kept on this device, set before the page shows. Shared by the
     admin, the login and the lock screen. --}}
<script>
    // Light or dark: the choice kept on this device ("auto" follows the device, also when it changes), set
    // before the page shows so it never flashes white.
    window.adminTheme = {
        get choice() { try { return localStorage.getItem('admin-theme') || 'auto'; } catch (error) { return 'auto'; } },
        apply() {
            const dark = this.choice === 'dark' || (this.choice === 'auto' && matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#1b1e25' : '#ffffff');
        },
        set(choice) {
            try { localStorage.setItem('admin-theme', choice); } catch (error) {}
            this.apply();
        },
    };
    adminTheme.apply();
    matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => adminTheme.apply());
</script>
