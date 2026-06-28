const CACHE_NAME = 'src-suyanto-v1';
const ASSETS_TO_CACHE = [
    '/login',
    '/css/style.css',
    '/images/icon-192.png',
    '/images/icon-512.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event (Network-first style for dynamic pages, caching offline fallbacks)
self.addEventListener('fetch', event => {
    // Only cache GET requests
    if (event.request.method !== 'GET') return;
    
    // Ignore Chrome extension requests, live-reloading or hot-reloading scripts
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) {
        // External assets, e.g. CDN fonts
        if (event.request.url.includes('cdnjs.cloudflare.com')) {
            event.respondWith(
                caches.match(event.request).then(cachedResponse => {
                    return cachedResponse || fetch(event.request);
                })
            );
        }
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                // If network response is valid, clone and cache it for assets/login
                if (networkResponse && networkResponse.status === 200) {
                    const clonedResponse = networkResponse.clone();
                    // Cache CSS, JS, and images dynamically
                    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|woff2|ico)$/) || url.pathname === '/login') {
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, clonedResponse);
                        });
                    }
                }
                return networkResponse;
            })
            .catch(() => {
                // Offline fallback
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // If requesting page and offline, return the cached login page
                    if (event.request.mode === 'navigate') {
                        return caches.match('/login');
                    }
                });
            })
    );
});
