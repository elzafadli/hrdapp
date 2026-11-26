const CACHE_NAME = 'hrdapp-cache-v1';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/favicon.ico'
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('PWA asset cache error:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate event
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
        })
    );
    self.clients.claim();
});

// Fetch event: Network-First strategy (presensi & data selalu realtime)
self.addEventListener('fetch', (event) => {
    // Abaikan request non-GET dan request AJAX/API/POST
    if (event.request.method !== 'GET') return;

    // Abaikan chrome-extension atau scheme non-http
    if (!event.request.url.startsWith('http')) return;

    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                // Update cache untuk aset statis ringan jika sukses
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        // Jangan cache response HTML admin/dinamis terlalu agresif
                        if (event.request.url.match(/\.(png|jpg|jpeg|svg|css|js|woff2|ico)$/)) {
                            cache.put(event.request, responseClone);
                        }
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                // Fallback ke cache jika offline
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.headers.get('accept')?.includes('text/html')) {
                        return caches.match('/');
                    }
                });
            })
    );
});
