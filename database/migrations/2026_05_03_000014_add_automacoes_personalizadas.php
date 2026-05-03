<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automacoes', function (Blueprint $table) {
            $table->boolean('personalizada')->default(false)->after('tipo');
            $table->string('gatilho', 30)->nullable()->after('personalizada');
            $table->decimal('valor_referencia', 12, 2)->nullable()->after('dias_offset');
        });

        // Tipos fixos continuam tendo unicidade (1 registro por tipo) — garantida
        // na lógica do controller. Remove a constraint do banco pra permitir
        // múltiplas com tipo='personalizada'.
        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropUnique(['tipo']);
        });
    }

    public function down(): void
    {
        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropColumn(['personalizada', 'gatilho', 'valor_referencia']);
            $table->unique('tipo');
        });
    }
};
