<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate por role dentro do painel admin. Uso:
 *
 *   Route::middleware('admin.role:admin,gerente')->group(...)
 *
 * Precisa rodar APÓS `admin.auth` — assume usuário autenticado. super_admin
 * sempre passa (pra impersonate continuar funcionando em qualquer rota).
 *
 * Hierarquia esperada:
 *   - super_admin: tudo (incluindo /super/*)
 *   - admin:       painel /admin/* completo
 *   - gerente:     painel /admin/* completo (igual admin pra fins operacionais)
 *   - atendente:   só caixa + consulta de cliente + ações operacionais
 *                  (sem configurações, sem deletar/editar cliente, sem CSV,
 *                   sem regras/recompensas/parceiros/roleta/sorteio).
 */
class AdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('admin.login');
        }

        if (!$user->hasRole(...$roles)) {
            // Requests AJAX/JSON recebem 403 estruturado. HTML recebe abort
            // pra deixar o handler de exceção desenhar a página de erro.
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'forbidden',
                    'message' => 'Sua função não permite acessar esta área.',
                ], 403);
            }
            abort(403, 'Sua função não permite acessar esta área.');
        }

        return $next($request);
    }
}
