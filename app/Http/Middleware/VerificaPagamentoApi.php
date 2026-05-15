<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Variante do VerificaPagamento pra rotas API (PWA cliente e PWA loja).
 *
 * Quando a empresa está em `bloqueio_total` (30+ dias de atraso ou
 * status cancelada/pausada), bloqueia endpoints que mexem em estado
 * financeiro do programa — registrar compra, gerar cupom, solicitar
 * resgate, girar roleta. Endpoints de LEITURA (dashboard, extrato,
 * me) continuam liberados pra cliente checar saldo/histórico.
 *
 * Apliquei em rotas escrita-pesada, não em GETs — operador inadimplente
 * que tenta operar caixa via PWA recebe 403 visível, e cliente final
 * que tenta consumir saldo entende pelo erro.
 *
 * Uso:
 *   Route::middleware('auth:sanctum', 'verifica.pagamento.api')->...
 */
class VerificaPagamentoApi
{
    public function handle(Request $request, Closure $next)
    {
        $authed = $request->user();
        $empresa = null;

        if ($authed instanceof User) {
            $empresa = $authed->empresa;
        } elseif ($authed instanceof Cliente) {
            $empresa = $authed->empresa;
        }

        if (!$empresa) return $next($request);

        if ($empresa->statusInadimplencia() === 'bloqueio_total') {
            return response()->json([
                'error' => 'empresa_bloqueada',
                'message' => 'Esta empresa está com a assinatura bloqueada. Entre em contato com o estabelecimento.',
            ], 403);
        }

        return $next($request);
    }
}
