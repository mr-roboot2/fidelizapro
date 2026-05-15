<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unique constraint em pesquisas_satisfacao:
 * (cliente_id, compra_id) — defesa em profundidade contra race.
 *
 * O PesquisaController::responder agora faz recheck dentro de lock, mas
 * o unique no banco é a garantia última: se uma race escapar (deploy
 * com instância antiga rodando em paralelo, etc), a inserção falha
 * com 23000 ao invés de criar duplicata.
 *
 * Em MySQL, múltiplas linhas com `compra_id = NULL` são permitidas
 * (avaliação geral por cliente — limitada via app + filtra
 * `whereNull('compra_id')`).
 *
 * Antes de criar o índice, deduplica entradas existentes pra cobrir
 * casos de race histórica (mantém a mais antiga).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pesquisas_satisfacao')) {
            return;
        }

        // Deduplica antes de criar índice — mantém a mais antiga.
        $duplicados = DB::table('pesquisas_satisfacao')
            ->select('cliente_id', 'compra_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('compra_id')
            ->groupBy('cliente_id', 'compra_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicados as $d) {
            DB::table('pesquisas_satisfacao')
                ->where('cliente_id', $d->cliente_id)
                ->where('compra_id', $d->compra_id)
                ->where('id', '!=', $d->keep_id)
                ->delete();
        }

        Schema::table('pesquisas_satisfacao', function (Blueprint $table) {
            $table->unique(['cliente_id', 'compra_id'], 'pesquisas_cliente_compra_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pesquisas_satisfacao', function (Blueprint $table) {
            $table->dropUnique('pesquisas_cliente_compra_unique');
        });
    }
};
