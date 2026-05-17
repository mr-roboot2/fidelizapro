<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use Closure;
use Illuminate\Http\Request;

/**
 * Espelho do RequireUser: garante que o token Sanctum pertence a um
 * App\Models\Cliente (não User da empresa).
 *
 * Sanctum não diferencia tokenable_type por padrão — sem este gate, um
 * User com token `pwa-loja` autenticava em endpoints /cliente/* lendo
 * (e potencialmente escrevendo) dados que mapeiam pra entidade Cliente.
 * A maioria dos endpoints quebra com 500 (User não tem `compras()`,
 * `transacoesPontos()`), mas alguns (dashboard) retornam dados zerados
 * com status 200 — confusão de identidade.
 *
 * Aplicar APÓS auth:sanctum:
 *   Route::middleware(['auth:sanctum', 'sanctum.cliente'])->group(...)
 */
class RequireCliente
{
    public function handle(Request $request, Closure $next)
    {
        $authed = $request->user();

        if (!$authed instanceof Cliente) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => 'Esta rota é exclusiva pra clientes.',
            ], 403);
        }

        return $next($request);
    }
}
