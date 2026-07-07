// RETC-CTS service worker — deliberately minimal and conservative.
//
// This app is a Livewire-driven admin system: almost every page is
// authenticated and stateful, and Livewire polls/POSTs to /livewire/update
// for every interaction. Caching any of that would show stale data or break
// interactivity, so this worker ONLY does two things:
//   1. Cache-first for hashed, versioned build assets (resources/build/*) —
//      safe because Vite gives every build a new filename.
//   2. Network-first (falling back to a cached copy, then an offline page)
//      for page navigations — makes the app tolerant of a flaky connection
//      without ever serving genuinely stale authenticated data.
// Everything else (Livewire's own requests, API-style calls, uploads) is
// left untouched and always goes to the network.

const CACHE_VERSION = 'retc-cts-v1';
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [OFFLINE_URL];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Never intercept anything but simple GETs — Livewire's update requests
    // are POST, and intercepting them would break every wire:click/model.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Leave Livewire's own asset/update endpoints and any non-navigation
    // API-style traffic entirely alone.
    if (url.pathname.startsWith('/livewire/')) {
        return;
    }

    // Vite's build output is content-hashed — safe to cache aggressively.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(CACHE_VERSION).then(async (cache) => {
                const cached = await cache.match(request);
                if (cached) return cached;

                const response = await fetch(request);
                cache.put(request, response.clone());
                return response;
            })
        );
        return;
    }

    // Page navigations: try the network first (so logged-in users always see
    // live data), fall back to the offline page only when the network is
    // actually unreachable.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
    }
});
