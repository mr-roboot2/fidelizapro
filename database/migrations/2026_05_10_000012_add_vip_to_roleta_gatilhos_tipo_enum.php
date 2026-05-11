<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE roleta_gatilhos MODIFY COLUMN tipo ENUM(
            'primeiro_cadastro',
            'aniversario',
            'indicacao',
            'compra_acima',
            'inativo_dias',
            'atingiu_pontos',
            'vip_gasto',
            'recorrente_compras'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE roleta_gatilhos MODIFY COLUMN tipo ENUM(
            'primeiro_cadastro',
            'aniversario',
            'indicacao',
            'compra_acima',
            'inativo_dias',
            'atingiu_pontos'
        ) NOT NULL");
    }
};
