<?php

namespace App\Http\Middleware;

use App\Models\Plano;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireModulo
{
    public function handle(Request $request, Closure $next, string $modulo)
    {
        $user = Auth::user();
        $empresa = $user?->empresa;
        if (!$empresa) {
            return $next($request);
        }

        $rotulo = Plano::rotulosModulos()[$modulo] ?? $modulo;

        if (!$empresa->temModulo($modulo)) {
            return redirect()
                ->route('admin.meu-plano.index')
                ->with('error', "O recurso \"{$rotulo}\" não está disponível no seu plano. Faça upgrade pra usar.");
        }

        // bloqueio_parcial (8-30 dias atraso): plano inclui o módulo, mas
        // a empresa está inadimplente — bloqueia módulos avançados
        // conforme Plano::MODULOS_AVANCADOS. Antes a string aparecia em
        // statusInadimplencia() mas nenhum middleware enforça, então
        // empresa atrasada continuava usando roleta/sorteios/whatsapp
        // (contrariando o comentário do Empresa::statusInadimplencia).
        if ($empresa->statusInadimplencia() === 'bloqueio_parcial'
            && in_array($modulo, Plano::MODULOS_AVANCADOS, true)) {
            return redirect()
                ->route('admin.meu-plano.index')
                ->with('error', "\"{$rotulo}\" está bloqueado por inadimplência. Pague a cobrança em aberto pra desbloquear.");
        }

        return $next($request);
    }
}
