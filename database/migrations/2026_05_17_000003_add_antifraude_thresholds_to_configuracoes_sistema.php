<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Limites de antifraude configuráveis. Antes hardcoded em
 * AtividadeSuspeitaController — admin sem acesso a código pra ajustar
 * em produção. Defaults preservam o comportamento atual (3 / 3 / 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->unsignedSmallInteger('antifraude_resgates_24h')->default(3)->after('max_resgates_24h');
            $table->unsignedSmallInteger('antifraude_ips_compartilhados')->default(3)->after('antifraude_resgates_24h');
            $table->unsignedSmallInteger('antifraude_cadastros_dia_ip')->default(3)->after('antifraude_ips_compartilhados');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn(['antifraude_resgates_24h', 'antifraude_ips_compartilhados', 'antifraude_cadastros_dia_ip']);
        });
    }
};
