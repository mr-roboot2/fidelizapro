<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    /**
     * Menu genérico de seleção de empresa — branding vem de ConfiguracaoSistema
     * (super admin), não de uma empresa específica.
     */
    public function appGenerico()
    {
        $config = ConfiguracaoSistema::instancia();
        return view('pwa.app_generico', compact('config'));
    }

    /**
     * Manifest do menu genérico — usa branding do super admin.
     */
    public function manifestGenerico()
    {
        $config = ConfiguracaoSistema::instancia();

        $logo = $config->logoUrl() ?? asset('app/icons/icon.svg');
        $scopePath = parse_url(url('/app/'), PHP_URL_PATH);

        $manifest = [
            'name' => $config->nome_sistema,
            'short_name' => $config->nome_sistema,
            'description' => $config->slogan ?: 'Programa de fidelidade. Acumule pontos e troque por prêmios.',
            'start_url' => $scopePath,
            'scope' => $scopePath,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => $config->cor_primaria,
            'lang' => 'pt-BR',
            'icons' => [
                [
                    'src' => $logo,
                    'sizes' => 'any',
                    'type' => $config->logo ? 'image/'.pathinfo($config->logo, PATHINFO_EXTENSION) : 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Renderiza a SPA com tema da empresa pré-aplicado.
     */
    public function app(string $slug)
    {
        $empresa = Empresa::where('slug', $slug)->where('ativo', true)->firstOrFail();
        return view('pwa.app', compact('empresa'));
    }

    /**
     * Manifest dinâmico por empresa (white label).
     */
    public function manifest(string $slug)
    {
        $empresa = Empresa::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $logo = $empresa->logo
            ? asset('storage/'.$empresa->logo)
            : asset('app/icons/icon.svg');

        $scopePath = parse_url(url("/app/{$slug}/"), PHP_URL_PATH);

        $manifest = [
            'name' => $empresa->nome.' — Fidelidade',
            'short_name' => $empresa->nome,
            'description' => 'Programa de fidelidade da '.$empresa->nome,
            'start_url' => $scopePath,
            'scope' => $scopePath,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => $empresa->cor_primaria,
            'lang' => 'pt-BR',
            'icons' => [
                [
                    'src' => $logo,
                    'sizes' => 'any',
                    'type' => $empresa->logo ? 'image/'.pathinfo($empresa->logo, PATHINFO_EXTENSION) : 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Service worker com escopo dinâmico por empresa.
     */
    public function serviceWorker(string $slug)
    {
        $empresa = Empresa::where('slug', $slug)->where('ativo', true)->firstOrFail();
        $base = parse_url(url("/app/{$slug}"), PHP_URL_PATH);
        $appBase = parse_url(url('/app'), PHP_URL_PATH);

        $js = <<<JS
const CACHE = 'fidelizapro-{$slug}-v5';
const ASSETS = [
    '{$base}/',
    '{$appBase}/style.css',
    '{$appBase}/app.js',
    '{$base}/manifest.json',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
    'https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js',
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

    if (url.pathname.includes('/api/')) {
        e.respondWith(fetch(e.request).then((r) => {
            const c = r.clone();
            caches.open(CACHE).then((cc) => cc.put(e.request, c));
            return r;
        }).catch(() => caches.match(e.request)));
        return;
    }

    // App shell: network-first para mudanças propagarem sem precisar limpar SW.
    const isAppShell = url.origin === location.origin && (
        url.pathname.endsWith('/app.js') ||
        url.pathname.endsWith('/style.css') ||
        url.pathname === '{$base}/' ||
        url.pathname === '{$base}'
    );
    if (isAppShell) {
        e.respondWith(fetch(e.request).then((r) => {
            if (r && r.status === 200) {
                const c = r.clone();
                caches.open(CACHE).then((cc) => cc.put(e.request, c));
            }
            return r;
        }).catch(() => caches.match(e.request).then((res) => res || caches.match('{$base}/'))));
        return;
    }

    e.respondWith(caches.match(e.request).then((r) => r || fetch(e.request).then((rr) => {
        if (rr && rr.status === 200) {
            const c = rr.clone();
            caches.open(CACHE).then((cc) => cc.put(e.request, c));
        }
        return rr;
    }).catch(() => caches.match('{$base}/'))));
});
JS;

        return response($js)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', $base.'/');
    }
}
