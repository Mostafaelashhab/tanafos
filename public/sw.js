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

// --- Web Push (PWA) ---------------------------------------------------------
// Ready for when push is configured (VAPID keys + a subscription store). The
// server sends a JSON payload: { title, body, url, icon }.
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = {}; }

    const title = data.title || 'Tanafos';
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon.svg',
        badge: '/icons/icon.svg',
        dir: 'rtl',
        lang: 'ar',
        data: { url: data.url || '/notifications' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/notifications';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if (client.url.includes(url) && 'focus' in client) return client.focus();
            }
            return clients.openWindow(url);
        })
    );
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
