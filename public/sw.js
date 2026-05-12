// Service Worker - Network-only strategy (NO CACHING AT ALL)
// All requests go directly to the network - 100% real-time, no offline support

const CACHE_NAME = 'sanadk-v1.0.0';

// Install event - skip waiting
self.addEventListener('install', (event) => {
    console.log('[SW] Installing - network-only mode (NO CACHING)');
    self.skipWaiting();
});

// Activate event - claim clients immediately
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating - network-only mode');
    event.waitUntil(self.clients.claim());
});

// Fetch event - pass all requests directly to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    const isImageRequest = event.request.destination === 'image' || url.pathname.match(/\.(png|jpg|jpeg|webp|svg)$/i);
    const isTileRequest = url.href.match(/tile|{z}|{x}|{y}|openstreetmap|cartocdn|wikimedia|opentopomap|arcgisonline/);

    if (isImageRequest || isTileRequest) {
        return; // Do not intercept image/tile requests
    }

    // Network-only strategy for all non-image requests
    event.respondWith(
        fetch(event.request)
            .then(response => response)
            .catch(error => {
                console.error('[SW] Network request failed:', error);
                return new Response(JSON.stringify({
                    error: 'Network unavailable',
                    message: 'Unable to fetch data - check your internet connection'
                }), {
                    status: 503,
                    statusText: 'Service Unavailable',
                    headers: { 'Content-Type': 'application/json' }
                });
            })
    );
});

// Handle messages from main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});