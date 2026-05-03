<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campanhas passam a ser globais por padrão (empresa_id null = todas
 * empresas). Quando uma empresa específica é definida, dispara só para
 * os clientes dela. Gerenciamento é feito pelo super admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campanhas', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
        Schema::table('campanhas', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Não reverte — as duas empresas que já tinham null não voltariam.
    }
};
