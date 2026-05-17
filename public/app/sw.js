const CACHE = 'fidelizapro-v9';

// Caminhos relativos ao escopo do SW (a própria pasta /app/).
// O index e o manifest são servidos dinamicamente pelo Laravel — não pré-cachear
// caminhos estáticos que não existem mais.
const ASSETS = [
    './',
    './style.css',
    './app.js',
    './manifest.json',
    './icons/icon.svg',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
    'https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js',
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

    // API: SEMPRE rede, NUNCA cache. Cache de /api/* autenticadas vazava
    // dados entre usuários do mesmo browser (cliente A logava, SW cacheava
    // dashboard de A, cliente A saía, cliente B logava — se a rede travasse
    // 1s, SW servia dados do A pro B). Não há fallback offline aqui.
    if (url.pathname.includes('/api/')) {
        e.respondWith(fetch(e.request));
        return;
    }

    // App shell (HTML/JS/CSS do próprio app): network-first com fallback cache.
    // Garante que mudanças em app.js / index.html cheguem ao cliente sem precisar
    // limpar o SW manualmente.
    const isAppShell = url.origin === location.origin && (
        url.pathname.endsWith('/app.js') ||
        url.pathname.endsWith('/style.css') ||
        url.pathname.endsWith('/index.html') ||
        url.pathname.endsWith('/app/') ||
        url.pathname.endsWith('/app')
    );
    if (isAppShell) {
        e.respondWith(
            fetch(e.request)
                .then((r) => {
                    if (r && r.status === 200) {
                        const copy = r.clone();
                        caches.open(CACHE).then((c) => c.put(e.request, copy));
                    }
                    return r;
                })
                .catch(() => caches.match(e.request).then((res) => res || caches.match('./')))
        );
        return;
    }

    // Demais (CDNs, ícones): cache-first
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
