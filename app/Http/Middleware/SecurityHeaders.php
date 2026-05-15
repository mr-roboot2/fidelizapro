<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers de segurança aplicados globalmente. CSP é "modo permissivo" para
 * compatibilidade com o stack atual (Tailwind CDN, jsDelivr, inline scripts
 * gerados pelo Blade); rotas PWA recebem versão menos restritiva por usarem
 * `eval` indireto via canvas-confetti / jsQR carregados dinamicamente.
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

        // CSP só em rotas administrativas. PWAs (/app, /loja, /caixa) ficam
        // de fora porque carregam libs (jsQR, canvas-confetti) e usam scripts
        // inline montados dinamicamente — uma CSP estrita quebraria o app.
        if (!$request->is('app/*', 'loja/*', 'caixa/*', 'api/*', 'webhook/*', 'storage/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; "
                ."img-src 'self' data: https:; "
                ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; "
                ."style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
                ."font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; "
                ."connect-src 'self'; "
                ."frame-ancestors 'self'; "
                ."base-uri 'self'; "
                ."form-action 'self'"
            );
        }

        return $response;
    }
}
