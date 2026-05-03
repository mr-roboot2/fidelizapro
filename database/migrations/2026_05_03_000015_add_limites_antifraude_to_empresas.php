<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedSmallInteger('rate_limit_auth')->default(10)->comment('Login/registro/OTP por minuto por IP');
            $table->unsignedSmallInteger('rate_limit_pdv')->default(60)->comment('Webhook PDV por minuto por IP');
            $table->unsignedSmallInteger('otp_max_por_telefone')->default(3)->comment('Códigos OTP por telefone em 15 min');
            $table->unsignedSmallInteger('otp_max_tentativas')->default(5)->comment('Tentativas erradas antes de invalidar código');
            $table->unsignedSmallInteger('max_resgates_24h')->default(3)->comment('Resgates por cliente em 24h');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'rate_limit_auth', 'rate_limit_pdv',
                'otp_max_por_telefone', 'otp_max_tentativas',
                'max_resgates_24h',
            ]);
        });
    }
};
