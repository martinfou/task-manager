/**
 * Google Tasks client — asset cache only (production).
 * Does not cache HTML, API, or Inertia routes; Tasks require network.
 * Build assets use network-first so new deploys are not stuck behind cache.
 * See docs/PWA.md.
 */
const CACHE = 'gt-assets-v2';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter(
                        (k) =>
                            k.startsWith('gt-assets-') && k !== CACHE,
                    )
                    .map((k) => caches.delete(k)),
            );
            await self.clients.claim();
        })(),
    );
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
        (async () => {
            try {
                const response = await fetch(event.request);
                if (response.ok && response.type === 'basic') {
                    const cache = await caches.open(CACHE);
                    await cache.put(event.request, response.clone());
                }
                return response;
            } catch {
                const cached = await caches.match(event.request);
                if (cached) {
                    return cached;
                }
                throw new Error('offline');
            }
        })(),
    );
});
