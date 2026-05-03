<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->string('horario_automacoes', 5)->default('09:00')->after('whatsapp_ativo');
            $table->string('horario_cashback', 5)->default('03:00')->after('horario_automacoes');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn(['horario_automacoes', 'horario_cashback']);
        });
    }
};
