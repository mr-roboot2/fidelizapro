<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roletas', function (Blueprint $table) {
            // Quantos giros únicos por IP por dia. null = sem limite.
            $table->unsignedSmallInteger('limite_giros_dia_por_ip')->nullable()->after('limite_giros_dia');
        });

        Schema::table('sorteios', function (Blueprint $table) {
            $table->unsignedSmallInteger('limite_bilhetes_dia_por_ip')->nullable()->after('max_bilhetes_por_cliente');
        });

        Schema::table('sorteio_bilhetes', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->after('referencia');
            $table->index(['sorteio_id', 'ip']);
        });
    }

    public function down(): void
    {
        Schema::table('sorteio_bilhetes', function (Blueprint $table) {
            $table->dropIndex(['sorteio_id', 'ip']);
            $table->dropColumn('ip');
        });
        Schema::table('sorteios', function (Blueprint $table) {
            $table->dropColumn('limite_bilhetes_dia_por_ip');
        });
        Schema::table('roletas', function (Blueprint $table) {
            $table->dropColumn('limite_giros_dia_por_ip');
        });
    }
};
