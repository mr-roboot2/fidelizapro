<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Bloqueia /install/* depois que a instalação foi concluída.
 *
 * Defesa em profundidade: checa DOIS sinais — `storage/installed.lock` E
 * `configuracoes_sistema.instalado_em IS NOT NULL`. Atacante que conseguir
 * apagar o lock no servidor (deploy errado, permissão fraca em /storage)
 * AINDA assim não reabre o wizard, porque a flag em DB persiste.
 *
 * Em troca, o operador legítimo que precisa reinstalar tem que zerar AMBOS:
 *   rm storage/installed.lock
 *   UPDATE configuracoes_sistema SET instalado_em = NULL;
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (file_exists(storage_path('installed.lock'))) {
            return response()->view('install.locked', [], 403);
        }

        // Tabela pode não existir ainda na 1ª instalação — try/catch.
        try {
            if (Schema::hasTable('configuracoes_sistema')) {
                $instalado = DB::table('configuracoes_sistema')
                    ->whereNotNull('instalado_em')
                    ->exists();
                if ($instalado) {
                    return response()->view('install.locked', [], 403);
                }
            }
        } catch (Throwable $e) {
            // DB indisponível na 1ª inst — segue pro wizard.
        }

        return $next($request);
    }
}
