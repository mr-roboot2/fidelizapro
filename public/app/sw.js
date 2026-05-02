const CACHE = 'fidelizapro-v3';

// Caminhos relativos ao escopo do SW (a própria pasta /app/)
const ASSETS = [
    './',
    './index.html',
    './style.css',
    './app.js',
    './manifest.json',
    './icons/icon.svg',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
    'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/lib/browser.min.js',
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE).then((c) =>
            Promise.allSettled(ASSETS.map((url) => c.add(url).catch(() => null)))
        )
    );
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((keys) =>
        Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ));
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;
    const url = new URL(e.request.url);

    // API: rede primeiro, fallback cache
    if (url.pathname.includes('/api/')) {
        e.respondWith(
            fetch(e.request)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(e.request, copy));
                    return res;
                })
                .catch(() => caches.match(e.request))
        );
        return;
    }

    // Demais: cache-first
    e.respondWith(
        caches.match(e.request).then((res) =>
            res || fetch(e.request).then((r) => {
                if (r && r.status === 200) {
                    const copy = r.clone();
                    caches.open(CACHE).then((c) => c.put(e.request, copy));
                }
                return r;
            }).catch(() => caches.match('./'))
        )
    );
});
