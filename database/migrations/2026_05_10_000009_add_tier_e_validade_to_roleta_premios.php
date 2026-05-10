<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roleta_premios', function (Blueprint $table) {
            // Modo quente: prêmio só aparece pra clientes com pontos >= tier.
            $table->unsignedInteger('tier_minimo_pontos')->nullable()->after('quantidade_max_dia');
            // Modo campanha: janela de disponibilidade do prêmio.
            $table->date('valido_de')->nullable()->after('tier_minimo_pontos');
            $table->date('valido_ate')->nullable()->after('valido_de');
        });
    }

    public function down(): void
    {
        Schema::table('roleta_premios', function (Blueprint $table) {
            $table->dropColumn(['tier_minimo_pontos', 'valido_de', 'valido_ate']);
        });
    }
};
