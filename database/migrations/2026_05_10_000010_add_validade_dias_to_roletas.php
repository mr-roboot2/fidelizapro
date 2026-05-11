<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roletas', function (Blueprint $table) {
            // null = prêmio nunca expira; default = 30 dias pra apresentar
            // o código de resgate no caixa.
            $table->unsignedSmallInteger('validade_dias')->nullable()->default(30)->after('pontos_consolacao');
        });
    }

    public function down(): void
    {
        Schema::table('roletas', function (Blueprint $table) {
            $table->dropColumn('validade_dias');
        });
    }
};
