<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag que controla se a página pública /cadastro está aberta. Default
 * true (mantém comportamento atual). Super admin pode desligar via
 * /super/configuracoes pra fechar o signup quando quiser triar empresas
 * manualmente, congelar leads durante um lançamento ou trocar de
 * estratégia comercial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->boolean('cadastro_publico_ativo')
                ->default(true)
                ->after('trial_dias_padrao');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn('cadastro_publico_ativo');
        });
    }
};
