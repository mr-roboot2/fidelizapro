<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automações passam a ser globais (uma config por tipo, válida pra todas
 * empresas). O disparo continua acontecendo por cliente da empresa, mas
 * o texto/template é compartilhado.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Deduplica: mantém 1 registro por tipo (o mais recente atualizado)
        $tipos = DB::table('automacoes')->distinct()->pluck('tipo');
        foreach ($tipos as $tipo) {
            $manterId = DB::table('automacoes')
                ->where('tipo', $tipo)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('automacoes')
                ->where('tipo', $tipo)
                ->where('id', '!=', $manterId)
                ->delete();
        }

        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'tipo']);
        });
        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropColumn('empresa_id');
        });
        Schema::table('automacoes', function (Blueprint $table) {
            $table->unique('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropUnique(['tipo']);
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['empresa_id', 'tipo']);
        });
    }
};
