const CACHE_NAME = 'finova-pwa-v2';

// Essential static assets to pre-cache on install
const PRECACHE_ASSETS = [
    '/',
    '/manifest.webmanifest',
    '/favicon.svg',
    '/apple-touch-icon.png',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/maskable-icon-512x512.png',
];

// Install Event: pre-cache core assets & activate immediately
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[SW] Pre-caching partial warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event: clean up obsolete caches & claim clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event Strategy:
// 1. Static Assets (build/, icons/, fonts/): Cache-First with Network fallback
// 2. Navigation / Pages: Network-First with cache fallback
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests from the same origin
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Ignore hot reload, telescope, or API non-cacheable calls
    if (url.pathname.startsWith('/@') || url.pathname.includes('/livewire/') || url.pathname.includes('/sanctum/')) {
        return;
    }

    // Static assets (Vite hashed bundles in build/, icons, images, fonts)
    const isStaticAsset =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.match(/\.(css|js|woff2|woff|ttf|png|svg|ico|jpg|jpeg|webp)$/);

    if (isStaticAsset) {
        // Cache-First strategy
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    return new Response('', { status: 408, statusText: 'Request timed out' });
                });
            })
        );
        return;
    }

    // HTML Navigation requests: Network-First strategy
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // If offline, attempt to serve cached page or cached root
                return caches.match(request).then((cachedResponse) => {
                    return cachedResponse || caches.match('/');
                });
            })
        );
    }
});

