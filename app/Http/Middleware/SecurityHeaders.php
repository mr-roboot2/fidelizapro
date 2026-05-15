<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers de segurança aplicados globalmente.
 *
 * CSP estratégia:
 *   - Admin/Super/Públicas: CSP relativamente estrita.
 *   - PWA (/app/*, /loja/*) e tela operacional /caixa/*: CSP relaxada
 *     (precisa `unsafe-inline` por causa de scripts inline gerados pelo
 *     Blade e `unsafe-eval` por causa do Tailwind CDN JIT, que usa Function()
 *     internamente pra gerar CSS em runtime).
 *   - API/Webhook/Storage: sem CSP (respostas JSON / arquivos, não HTML).
 *
 * Mesmo com `unsafe-inline`+`unsafe-eval`, a CSP do PWA continua valiosa:
 *   - `script-src` whitelistado bloqueia injeção apontando pra origens
 *     desconhecidas (jsdelivr só, sem unpkg/jsfiddle/atacante.com).
 *   - `frame-ancestors 'self'` bloqueia clickjacking.
 *   - `base-uri 'self'` bloqueia <base href="//evil">.
 *   - `form-action 'self'` bloqueia exfiltração via <form action="//evil">.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(self), payment=()');

        // HSTS: força HTTPS no domínio por 1 ano. Só em production+HTTPS pra
        // não quebrar dev local sem TLS. includeSubDomains assume que tudo no
        // domínio é HTTPS — se houver subdomínio HTTP, remova essa diretiva.
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // API, webhook e storage não retornam HTML — pular CSP.
        if ($request->is('api/*', 'webhook/*', 'storage/*')) {
            return $response;
        }

        $csp = $this->csp();
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * Constrói a CSP usada em respostas HTML. Mesma policy pro painel admin,
     * PWA cliente, PWA loja e telas públicas — Tailwind CDN JIT e libs
     * versionadas via jsDelivr são comuns a todas, e padronizar reduz risco
     * de gap entre páginas.
     */
    protected function csp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            // Imagens: self + data: (QR codes, ícones inline) + https: (logos
            // de empresas hospedados em qualquer CDN configurada pelo admin).
            "img-src 'self' data: https:",
            // Scripts: Tailwind CDN JIT precisa de unsafe-eval. Inline scripts
            // são extensos no Blade — unsafe-inline mantido. Whitelist de
            // origens externas é estrita (Tailwind, jsDelivr, Turnstile).
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://challenges.cloudflare.com",
            // Style: inline obrigatório (estilo dinâmico de cor da empresa)
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
            // Connect: chamadas API ficam no mesmo domínio. Turnstile faz
            // XHR pra siteverify mas isso é backend; widget client-side só
            // postMessage interno via iframe, sem connect-src cross-origin.
            "connect-src 'self'",
            // Frame: Turnstile renderiza widget em iframe próprio (managed/
            // invisible mode). Sem isso o widget não aparece.
            "frame-src 'self' https://challenges.cloudflare.com",
            // Hardening anti-clickjacking / anti-redirect:
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            // Worker-src precisa de blob: pra service worker registrar
            "worker-src 'self' blob:",
            // Object/embed bloqueados (Flash/Java históricos)
            "object-src 'none'",
        ]);
    }
}
