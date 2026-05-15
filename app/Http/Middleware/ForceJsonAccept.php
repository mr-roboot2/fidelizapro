<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Força `Accept: application/json` em todas as rotas do grupo onde for
 * aplicado. Usado no grupo `api` pra que o handler default do Laravel
 * (`Authenticate::unauthenticated`, `ValidationException::render`, etc.)
 * sempre veja `$request->expectsJson() === true` e devolva JSON.
 *
 * Sem isso, cliente API que não setava o header recebia 500
 * RouteNotFoundException('login') ou redirect HTML em vez de 401/422
 * JSON.
 */
class ForceJsonAccept
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
