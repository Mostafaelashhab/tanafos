// banha.shop service worker — shared-hosting friendly (no build step).
const VERSION = 'v1';
const CACHE = `banha-${VERSION}`;
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon.svg', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only handle same-origin GET; never touch POST/auth/Livewire updates.
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Page navigations: network-first, fall back to the offline page.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Hashed build assets + icons: cache-first (stale-while-revalidate).
    const url = new URL(request.url);
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request)
                    .then((resp) => {
                        if (resp.ok) {
                            const copy = resp.clone();
                            caches.open(CACHE).then((c) => c.put(request, copy));
                        }
                        return resp;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        );
    }
});
