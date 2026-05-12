const CACHE_NAME = 'sanadk-v1.0.0';
const STATIC_CACHE = 'sanadk-static-v1.0.0';
const API_CACHE = 'sanadk-api-v1.0.0';

// Static assets to cache
const STATIC_ASSETS = [
  '/',
  '/css/app.css',
  '/css/bootstrap.rtl.min.css',
  '/css/fontawesome.css',
  '/css/sanadk-style.css',
  '/css/leaflet.css',
  '/css/leaflet-routing-machine.css',
  '/js/main.js',
  '/js/map.js',
  '/js/chart.min.js',
  '/js/bootstrap.bundle.min.js',
  '/js/leaflet.js',
  '/js/leaflet-routing-machine.js',
  '/img/logo.png',
  '/manifest.json',
  '/offline.html',
  '/webfonts/fa-brands-400.woff2',
  '/webfonts/fa-regular-400.woff2',
  '/webfonts/fa-solid-900.woff2',
  '/webfonts/fa-v4compatibility.woff2'
];

// External resources that should not be cached
const EXTERNAL_PATTERNS = [
  /cdnjs\.cloudflare\.com/,
  /cdn\.jsdelivr\.net/,
  /fonts\.googleapis\.com/,
  /tile\.openstreetmap\.org/,
  /tile\.opentopomap\.org/,
  /server\.arcgisonline\.com/,
  /\{s\}\.tile\.cartocdn\.com/,
  /overpass-api\.de/,
  /nominatim\.openstreetmap\.org/
];

// External resources that should be cached (critical ones)
const CACHEABLE_EXTERNAL_PATTERNS = [
  /unpkg\.com\/leaflet/,  // Leaflet library
  /cdn\.jsdelivr\.net\/npm\/leaflet/,  // Alternative Leaflet CDN
  /cdn\.jsdelivr\.net\/npm\/leaflet-routing-machine/  // Routing machine
];

// API endpoints that should use network-first strategy
const API_PATTERNS = [
  /\/api\//
];

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('[SW] Installing Service Worker');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .catch(error => {
        console.error('[SW] Failed to cache static assets:', error);
      })
  );
  // Force activation
  self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
  console.log('[SW] Activating Service Worker');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== STATIC_CACHE && cacheName !== API_CACHE) {
            console.log('[SW] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      // Take control of all clients
      return self.clients.claim();
    })
  );
});

// Fetch event - handle different types of requests
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip external resources that should not be cached
  if (EXTERNAL_PATTERNS.some(pattern => pattern.test(url.href))) {
    console.log('[SW] Skipping external resource:', url.href);
    return;
  }

  // Handle cacheable external resources (like Leaflet)
  if (CACHEABLE_EXTERNAL_PATTERNS.some(pattern => pattern.test(url.href))) {
    event.respondWith(cacheFirstStrategy(event.request));
    return;
  }

  // Handle API requests with network-first strategy
  if (API_PATTERNS.some(pattern => pattern.test(url.pathname))) {
    event.respondWith(networkFirstStrategy(event.request));
    return;
  }

  // Handle static assets with cache-first strategy
  event.respondWith(cacheFirstStrategy(event.request));
});

// Cache-first strategy for static assets
async function cacheFirstStrategy(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      console.log('[SW] Serving from cache:', request.url);
      return cachedResponse;
    }

    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      // Cache successful responses
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('[SW] Cache-first strategy failed:', error);

    // Return offline fallback for navigation requests
    if (request.mode === 'navigate') {
      const offlineResponse = await caches.match('/offline.html');
      if (offlineResponse) {
        return offlineResponse;
      }
    }

    // Return basic error response
    return new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable',
      headers: { 'Content-Type': 'text/plain' }
    });
  }
}

// Network-first strategy for API requests
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      // Cache successful API responses
      const cache = await caches.open(API_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('[SW] Network-first strategy failed, trying cache:', error);

    // Try to serve from cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      console.log('[SW] Serving API from cache:', request.url);
      return cachedResponse;
    }

    // Return error response
    return new Response(JSON.stringify({
      error: 'Network unavailable',
      message: 'Unable to fetch data. Please check your connection.'
    }), {
      status: 503,
      statusText: 'Service Unavailable',
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Handle messages from main thread
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});