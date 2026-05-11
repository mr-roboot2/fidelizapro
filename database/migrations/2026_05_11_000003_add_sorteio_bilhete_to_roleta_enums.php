<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE roleta_premios MODIFY COLUMN tipo ENUM('recompensa','pontos','nova_chance','nada','sorteio_bilhete') NOT NULL");
        DB::statement("ALTER TABLE roleta_giros MODIFY COLUMN tipo_resultado ENUM('recompensa','pontos','nova_chance','consolacao','sorteio_bilhete') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE roleta_premios MODIFY COLUMN tipo ENUM('recompensa','pontos','nova_chance','nada') NOT NULL");
        DB::statement("ALTER TABLE roleta_giros MODIFY COLUMN tipo_resultado ENUM('recompensa','pontos','nova_chance','consolacao') NOT NULL");
    }
};
