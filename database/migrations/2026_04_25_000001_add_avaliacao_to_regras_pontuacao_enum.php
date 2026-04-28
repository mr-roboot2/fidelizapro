<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE regras_pontuacao MODIFY COLUMN tipo ENUM('compra','aniversario','indicacao','primeira_compra','cadastro','avaliacao') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE regras_pontuacao MODIFY COLUMN tipo ENUM('compra','aniversario','indicacao','primeira_compra','cadastro') NOT NULL");
    }
};
