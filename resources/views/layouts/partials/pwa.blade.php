{{-- The admin as an app on the phone's home screen: manifest, icons and its service worker (see public/sw-admin.js) --}}
<link rel="manifest" href="{{ asset('admin-manifest.json') }}">
<meta name="theme-color" content="#ffffff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="ICD Admin">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="{{ asset('icons/admin-apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register(@js(asset('sw-admin.js')), { scope: @js(url('/') . '/') }).catch(() => {}));
    }
</script>
