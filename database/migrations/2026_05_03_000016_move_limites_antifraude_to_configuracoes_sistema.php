<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Decisão revertida: limites antifraude voltam a ser globais (uma config
 * no super admin que vale pra todas empresas), não por empresa. As
 * colunas em empresas são descartadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->unsignedSmallInteger('rate_limit_auth')->default(10)->after('horario_cashback');
            $table->unsignedSmallInteger('rate_limit_pdv')->default(60)->after('rate_limit_auth');
            $table->unsignedSmallInteger('otp_max_por_telefone')->default(3)->after('rate_limit_pdv');
            $table->unsignedSmallInteger('otp_max_tentativas')->default(5)->after('otp_max_por_telefone');
            $table->unsignedSmallInteger('max_resgates_24h')->default(3)->after('otp_max_tentativas');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'rate_limit_auth', 'rate_limit_pdv',
                'otp_max_por_telefone', 'otp_max_tentativas',
                'max_resgates_24h',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedSmallInteger('rate_limit_auth')->default(10);
            $table->unsignedSmallInteger('rate_limit_pdv')->default(60);
            $table->unsignedSmallInteger('otp_max_por_telefone')->default(3);
            $table->unsignedSmallInteger('otp_max_tentativas')->default(5);
            $table->unsignedSmallInteger('max_resgates_24h')->default(3);
        });

        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn([
                'rate_limit_auth', 'rate_limit_pdv',
                'otp_max_por_telefone', 'otp_max_tentativas',
                'max_resgates_24h',
            ]);
        });
    }
};
