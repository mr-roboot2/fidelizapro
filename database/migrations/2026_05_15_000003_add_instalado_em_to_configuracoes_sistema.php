<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona flag persistente de instalação concluída em
 * configuracoes_sistema. Defesa em profundidade — atacante que apaga o
 * storage/installed.lock NÃO conseguirá reabrir /install/* porque o
 * middleware EnsureNotInstalled também checa esta flag.
 *
 * Migrations rodando em sistemas já instalados marcam `instalado_em = now()`
 * automaticamente (a tabela já existe e a aplicação está em produção).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->timestamp('instalado_em')->nullable()->after('updated_at');
        });

        // Sistemas que já existiam (rodando migrations num banco antigo): se a
        // tabela tem qualquer linha, é porque o wizard rodou no passado.
        // Marca como instalado pra não permitir reabertura via /install.
        if (DB::table('configuracoes_sistema')->exists()) {
            DB::table('configuracoes_sistema')->update(['instalado_em' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn('instalado_em');
        });
    }
};
