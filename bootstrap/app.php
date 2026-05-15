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
use App\Http\Middleware\RequireUser;
use App\Http\Middleware\SuperAdminAuth;
use App\Http\Middleware\EnsureNotInstalled;
use App\Http\Middleware\RequirePasswordChanged;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerificaPagamento;
use App\Http\Middleware\VerificaPagamentoApi;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Carrega rotas de webhook com middleware `api` (stateless): sem
        // sessions, sem CSRF, sem cookies. Antes ficavam em web.php
        // herdando StartSession — atacante mandava POSTs forjados e
        // criava session files em massa.
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')
                ->group(__DIR__.'/../routes/webhooks.php');
        },
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
            'verifica.pagamento.api' => VerificaPagamentoApi::class,
            'senha.definitiva' => RequirePasswordChanged::class,
            'captcha' => RequireCaptcha::class,
            'sanctum.user' => RequireUser::class,
        ]);

        // Confia em proxies pra Request::ip() retornar IP real do cliente
        // (não o IP da edge). Sem isso EmpresaThrottle, RateLimiter de login,
        // antifraude IP da roleta e logs de auditoria veem todos os clientes
        // como o mesmo IP do proxy → rate limit virou global.
        //
        // 'private_ranges' funciona pra proxy on-premise (Nginx local,
        // CloudPanel). Pra Cloudflare (e qualquer CDN com edge em IP público),
        // a lista oficial está em https://www.cloudflare.com/ips/. Pode
        // sobrescrever via TRUSTED_PROXIES no .env (CSV) — em produção atrás
        // de CF sempre setar essa env. Em desenvolvimento local, deixar
        // vazio cai no default 'private_ranges'.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES')
                ? array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES'))))
                : 'private_ranges',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Headers de segurança globais (X-Frame-Options, X-Content-Type-Options,
        // Referrer-Policy, Permissions-Policy, HSTS em production+HTTPS e CSP
        // em rotas admin).
        $middleware->append(SecurityHeaders::class);

        // API consumida via Bearer token (sem cookies/CSRF). Não usar statefulApi(),
        // que marcaria requests do mesmo domínio como SPA e exigiria X-XSRF-TOKEN.

        // Webhooks foram migrados pra routes/webhooks.php sob middleware
        // `api` (stateless) — não precisam mais do CSRF except aqui, mas
        // mantemos a lista pra defesa em profundidade caso alguém tente
        // declarar webhook dentro do web group novamente.
        $middleware->validateCsrfTokens(except: [
            'webhook/pagamento/*',
            'webhook/pix',
            'webhook/pix/*',
            'webhook/whatsapp/*',
        ]);

        // Força `Accept: application/json` em todas as rotas /api/*. Sem isso,
        // clientes que não setam o header recebem redirect/HTML do handler
        // default em exceptions (AuthenticationException tentando route('login'),
        // ValidationException 302, ModelNotFoundException 404 HTML, etc).
        // Aplicado no grupo `api` (prepend = roda antes de tudo no grupo).
        $middleware->prependToGroup('api', function ($request, $next) {
            $request->headers->set('Accept', 'application/json');
            return $next($request);
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Toda response JSON pra /api/* + qualquer Accept: json.
        // Combinado com o middleware que prepend Accept: application/json
        // no grupo api (acima), garante que TODAS as exceptions seguem o
        // caminho JSON do handler default — 401 pra auth, 422 pra validation,
        // 404 pra model not found, etc.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
