// Service worker of the repertoire app (/repertoire): keeps what the app needs to work offline.
// - Pages of the app: from the network when online (the copy is updated), from the copy when offline or
//   when the server fails.
// - Scripts, styles, fonts and icons: from the copy when there is one (updated in the background).
// - Audio files: from the copy saved by "Download for offline", otherwise from the network. Audio players ask for
//   parts of the file (ranges), so the copy is served in parts too.
// The cache names are also used by the page (resources/views/site/setlist.blade.php).
const PAGES = 'repertoire-pages-v1';
const ASSETS = 'repertoire-assets-v1';
const AUDIO = 'repertoire-audio-v1';

// Site folder ("/" or "/subfolder/"), from the scope ".../repertoire".
const BASE = new URL(self.registration.scope).pathname.replace(/repertoire\/?$/, '');
// Copy of the last page opened, shown offline when the exact address was never saved.
const LAST_PAGE = `${BASE}repertoire?offline-last`;
const ASSET_HOSTS = ['cdnjs.cloudflare.com', 'fonts.bunny.net'];

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (request.mode === 'navigate' && sameOrigin && url.pathname.startsWith(`${BASE}repertoire`)) {
        event.respondWith(page(request));
    } else if (sameOrigin && url.pathname.startsWith(`${BASE}audio/`)) {
        event.respondWith(audio(request));
    } else if ((sameOrigin && /^(build|js|icons|img)\/|^manifest\.json$/.test(url.pathname.slice(BASE.length))) || ASSET_HOSTS.includes(url.hostname)) {
        event.respondWith(asset(request));
    }
});

async function page(request) {
    const cache = await caches.open(PAGES);
    const saved = async () => (await cache.match(request, { ignoreSearch: true })) || (await cache.match(LAST_PAGE));
    try {
        const response = await fetch(request);
        if (response.ok && !response.redirected) {
            cache.put(request, response.clone());
            cache.put(LAST_PAGE, response.clone());
        }
        // The server is reachable but failing (e.g. 502): the saved copy is more useful than the error page.
        if (response.status >= 500) return (await saved()) || response;
        return response;
    } catch (error) {
        return (await saved()) || Response.error();
    }
}

async function asset(request) {
    const cache = await caches.open(ASSETS);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok) cache.put(request, response.clone());
            return response;
        })
        .catch(() => cached || Response.error());
    return cached || network;
}

async function audio(request) {
    const cache = await caches.open(AUDIO);
    const cached = await cache.match(request.url);
    if (!cached) return fetch(request);

    const range = /^bytes=(\d*)-(\d*)$/.exec(request.headers.get('range') || '');
    if (!range) return cached;
    const blob = await cached.blob();
    const start = range[1] === '' ? Math.max(0, blob.size - Number(range[2])) : Number(range[1]);
    const end = range[1] !== '' && range[2] !== '' ? Math.min(Number(range[2]), blob.size - 1) : blob.size - 1;
    return new Response(blob.slice(start, end + 1), {
        status: 206,
        headers: {
            'Content-Type': cached.headers.get('Content-Type') || 'audio/mpeg',
            'Content-Range': `bytes ${start}-${end}/${blob.size}`,
            'Content-Length': String(end - start + 1),
            'Accept-Ranges': 'bytes',
        },
    });
}
