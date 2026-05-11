<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sorteios MODIFY COLUMN status ENUM('planejado','ativo','sorteado','finalizado','cancelado') NOT NULL DEFAULT 'planejado'");
    }

    public function down(): void
    {
        DB::statement("UPDATE sorteios SET status='sorteado' WHERE status='finalizado'");
        DB::statement("ALTER TABLE sorteios MODIFY COLUMN status ENUM('planejado','ativo','sorteado','cancelado') NOT NULL DEFAULT 'planejado'");
    }
};
