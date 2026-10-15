const CACHE_NAME = 'disi-player-media-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    if (event.request.destination !== 'image' && event.request.destination !== 'video') return;
    event.respondWith(cacheFirst(event.request));
});

self.addEventListener('message', (event) => {
    const message = event.data ?? {};
    if (message.type === 'CACHE_MEDIA') event.waitUntil(cacheMedia(message.urls));
    if (message.type === 'CLEAR_CACHE') event.waitUntil(caches.delete(CACHE_NAME));
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok || response.type === 'opaque') await cache.put(request, response.clone());
        return response;
    } catch {
        return new Response('', { status: 503, statusText: 'Offline' });
    }
}

async function cacheMedia(urls) {
    if (!Array.isArray(urls)) return;
    const cache = await caches.open(CACHE_NAME);

    await Promise.allSettled(urls.filter((url) => typeof url === 'string').map(async (url) => {
        const request = new Request(url, { mode: 'no-cors' });
        if (await cache.match(request)) return;
        const response = await fetch(request);
        if (response.ok || response.type === 'opaque') await cache.put(request, response);
    }));
}
