// Service worker of the installed admin app: nothing of the admin is kept (it always needs the server); only the
// pages opened without a connection get a short "no connection" page instead of the browser's error.
// The repertoire app (/repertoire) has its own service worker (sw.js), which takes over its pages.
const OFFLINE = `<!doctype html><html lang="pt"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sem ligação</title><body style="margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,sans-serif;background:#f4f5f7;color:#111827;text-align:center">
<div style="padding:2rem"><p style="font-size:2.5rem;margin:0">📡</p><h1 style="font-size:1.2rem">Sem ligação à internet</h1>
<p style="color:#6b7280">O admin precisa de internet. Volte a tentar quando estiver ligado.</p>
<button onclick="location.reload()" style="margin-top:1rem;padding:.7rem 1.4rem;border:0;border-radius:.6rem;background:#f8a925;color:#fff;font-weight:700;font-size:1rem">Tentar de novo</button></div>`;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(fetch(event.request).catch(() => new Response(OFFLINE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
});
