const CACHE = 'fidelizapro-loja-v1';
const ASSETS = [
    '/loja/',
    '/loja/app.js',
    '/loja/manifest.json',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
    'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js',
];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) =>
        Promise.allSettled(ASSETS.map((u) => c.add(u).catch(() => null)))
    ));
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

    // API: rede primeiro (sem cache — dados sempre atualizados)
    if (url.pathname.startsWith('/api/')) return;

    // App shell: network-first pra mudanças propagarem sem limpar SW
    const isAppShell = url.origin === location.origin && (
        url.pathname === '/loja/' ||
        url.pathname === '/loja' ||
        url.pathname.endsWith('/loja/app.js') ||
        url.pathname.endsWith('/loja/manifest.json')
    );
    if (isAppShell) {
        e.respondWith(fetch(e.request).then((r) => {
            if (r && r.status === 200) {
                const c = r.clone();
                caches.open(CACHE).then((cc) => cc.put(e.request, c));
            }
            return r;
        }).catch(() => caches.match(e.request).then((res) => res || caches.match('/loja/'))));
        return;
    }

    // Demais (CDNs): cache-first
    e.respondWith(caches.match(e.request).then((r) => r || fetch(e.request).then((rr) => {
        if (rr && rr.status === 200) {
            const c = rr.clone();
            caches.open(CACHE).then((cc) => cc.put(e.request, c));
        }
        return rr;
    }).catch(() => null)));
});
