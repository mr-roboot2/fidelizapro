<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // 'ambos' mantém o comportamento atual; 'pontos' ou 'cashback' isola
            $table->enum('modo_fidelidade', ['pontos', 'cashback', 'ambos'])
                ->default('ambos')
                ->after('cashback_percentual');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('modo_fidelidade');
        });
    }
};
