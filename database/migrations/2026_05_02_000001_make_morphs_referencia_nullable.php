<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Torna as colunas referencia_type / referencia_id nullable em
 * transacoes_pontos e movimentos_cashback.
 *
 * Motivo: créditos sem origem objeto (bônus de cadastro, ajuste manual,
 * aniversário) precisam gravar referencia=null. As migrations originais
 * usaram $table->morphs() que cria as colunas NOT NULL — cadastro e
 * qualquer ajuste manual quebravam com SQLSTATE[23000].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacoes_pontos', function (Blueprint $table) {
            $table->string('referencia_type')->nullable()->change();
            $table->unsignedBigInteger('referencia_id')->nullable()->change();
        });

        Schema::table('movimentos_cashback', function (Blueprint $table) {
            $table->string('referencia_type')->nullable()->change();
            $table->unsignedBigInteger('referencia_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // sem rollback — voltar pra NOT NULL bloquearia ajustes manuais e
        // bônus de cadastro, que são casos legítimos de referencia null.
    }
};
