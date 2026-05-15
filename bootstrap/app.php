<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\AdminRole;
use App\Http\Middleware\EmpresaScope;
use App\Http\Middleware\EmpresaThrottle;
use App\Http\Middleware\RequireCaptcha;
use App\Http\Middleware\RequireModulo;
use App\Http\Middleware\SuperAdminAuth;
use App\Http\Middleware\EnsureNotInstalled;
use App\Http\Middleware\RequirePasswordChanged;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerificaPagamento;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.auth' => AdminAuth::class,
            'admin.role' => AdminRole::class,
            'empresa.scope' => EmpresaScope::class,
            'super.auth' => SuperAdminAuth::class,
            'install.gate' => EnsureNotInstalled::class,
            'empresa.throttle' => EmpresaThrottle::class,
            'modulo' => RequireModulo::class,
            'verifica.pagamento' => VerificaPagamento::class,
            'senha.definitiva' => RequirePasswordChanged::class,
            'captcha' => RequireCaptcha::class,
        ]);

        // Confia em proxies (CloudPanel, Cloudflare, Nginx reverse-proxy).
        // Sem isso o Request::ip() volta o IP do proxy interno e o throttle
        // por IP/EmpresaThrottle vira contagem global (rate limit quebrado).
        // 'private_ranges' aceita só ranges privados — seguro em qualquer
        // setup. Se estiver atrás de IPs públicos específicos (Cloudflare),
        // configure CLOUDFLARE_IPS / use 'at' com lista explícita.
        $middleware->trustProxies(at: 'private_ranges');

        // Headers de segurança globais (X-Frame-Options, X-Content-Type-Options,
        // Referrer-Policy, Permissions-Policy, HSTS em production+HTTPS e CSP
        // em rotas admin).
        $middleware->append(SecurityHeaders::class);

        // API consumida via Bearer token (sem cookies/CSRF). Não usar statefulApi(),
        // que marcaria requests do mesmo domínio como SPA e exigiria X-XSRF-TOKEN.

        // Webhooks de gateways externos não têm CSRF token
        $middleware->validateCsrfTokens(except: [
            'webhook/pagamento/*',
            'webhook/pix/*',
            'webhook/whatsapp/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
