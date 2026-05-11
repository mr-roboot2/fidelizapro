<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireModulo
{
    public function handle(Request $request, Closure $next, string $modulo)
    {
        $user = Auth::user();
        $empresa = $user?->empresa;

        if ($empresa && !$empresa->temModulo($modulo)) {
            $rotulo = \App\Models\Plano::MODULOS_DISPONIVEIS[$modulo] ?? $modulo;
            return redirect()
                ->route('admin.meu-plano.index')
                ->with('error', "O recurso \"{$rotulo}\" não está disponível no seu plano. Faça upgrade pra usar.");
        }

        return $next($request);
    }
}
