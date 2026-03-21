/**
 * Google Tasks client — asset cache only (production).
 * Does not cache HTML, API, or Inertia routes; Tasks require network.
 * See docs/PWA.md.
 */
const CACHE = 'gt-assets-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (!url.pathname.startsWith('/build/')) {
        return;
    }

    event.respondWith(
        caches.open(CACHE).then((cache) =>
            cache.match(event.request).then((cached) => {
                if (cached) {
                    return cached;
                }

                return fetch(event.request).then((response) => {
                    if (response.ok && response.type === 'basic') {
                        cache.put(event.request, response.clone());
                    }

                    return response;
                });
            }),
        ),
    );
});
