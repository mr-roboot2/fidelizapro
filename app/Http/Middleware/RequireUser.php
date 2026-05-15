<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Garante que o token Sanctum pertence a um App\Models\User (não Cliente).
 *
 * Sanctum não diferencia tokenable_type por padrão — tanto Cliente quanto
 * User usam HasApiTokens. Sem este gate, um cliente com token válido
 * autentica em endpoints /loja/*, conseguindo listar TODOS os clientes
 * da empresa, criar clientes em nome da empresa, lançar compras, etc.
 *
 * Aplicar APÓS auth:sanctum:
 *   Route::middleware(['auth:sanctum', 'sanctum.user'])->group(...)
 */
class RequireUser
{
    public function handle(Request $request, Closure $next)
    {
        $authed = $request->user();

        if (!$authed instanceof User) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'Esta rota só aceita operadores da loja, não clientes.',
            ], 403);
        }

        return $next($request);
    }
}
