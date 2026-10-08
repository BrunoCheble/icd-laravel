// Service worker of the repertoire app (/repertoire). For now it only makes the app installable: every request
// still goes to the network as usual. Offline copies of the setlists can be added here later.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});
