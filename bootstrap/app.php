<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\EmpresaScope;
use App\Http\Middleware\SuperAdminAuth;
use App\Http\Middleware\EnsureNotInstalled;

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
            'empresa.scope' => EmpresaScope::class,
            'super.auth' => SuperAdminAuth::class,
            'install.gate' => EnsureNotInstalled::class,
        ]);

        // API consumida via Bearer token (sem cookies/CSRF). Não usar statefulApi(),
        // que marcaria requests do mesmo domínio como SPA e exigiria X-XSRF-TOKEN.

        // Webhooks de gateways externos não têm CSRF token
        $middleware->validateCsrfTokens(except: [
            'webhook/pagamento/*',
            'webhook/whatsapp/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
