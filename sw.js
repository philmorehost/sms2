const CACHE_NAME = 'bulksms-cache-v2'; // Updated cache name to trigger update
const urlsToCache = [
  '/',
  // '/login.php' has been removed to prevent pre-caching
  '/css/main.css',
  '/js/main.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);

  // Define pages that should always be fetched from the network and never cached.
  const noCachePages = ['/login.php', '/register.php', '/forgot-password.php'];

  // If the request is for one of the no-cache pages, bypass the cache entirely.
  if (noCachePages.includes(requestUrl.pathname)) {
    event.respondWith(fetch(event.request));
    return; // Important: stop further execution for these pages.
  }

  // For all other requests, use the standard cache-first strategy.
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // If the response is in the cache, return it.
        if (response) {
          return response;
        }
        // Otherwise, fetch it from the network.
        return fetch(event.request);
      }
    )
  );
});

// Add an activate event listener to clean up old caches.
// This is crucial for ensuring the old cached login.php is removed.
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          // If a cache's name is different from our new cache name, delete it.
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});