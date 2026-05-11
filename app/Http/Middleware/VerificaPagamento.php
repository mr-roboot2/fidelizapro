<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bloqueio total: quando a empresa está com cobrança > 30 dias em atraso
 * (ou status cancelada/pausada), só consegue acessar as rotas essenciais
 * (meu-plano e logout). Tentar acessar qualquer outra coisa redireciona.
 */
class VerificaPagamento
{
    private const ROTAS_LIBERADAS = [
        'admin.meu-plano.*',
        'admin.logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $empresa = Auth::user()?->empresa;
        if (!$empresa) return $next($request);

        if ($empresa->statusInadimplencia() !== 'bloqueio_total') {
            return $next($request);
        }

        foreach (self::ROTAS_LIBERADAS as $padrao) {
            if ($request->routeIs($padrao)) return $next($request);
        }

        return redirect()
            ->route('admin.meu-plano.index')
            ->with('error', 'Sua assinatura está bloqueada. Regularize pra voltar a usar o sistema.');
    }
}
