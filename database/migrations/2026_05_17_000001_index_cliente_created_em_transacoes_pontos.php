<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extrato do PWA cliente (`Api\ClienteController::extrato`) faz
 * `cliente->transacoesPontos()->latest()` — query é
 * WHERE cliente_id = ? ORDER BY created_at DESC.
 *
 * Os índices existentes são `(empresa_id, cliente_id)` e
 * `(empresa_id, created_at)`. O primeiro filtra mas força filesort no
 * ORDER BY; o segundo não cobre o WHERE por cliente. Adicionar
 * (cliente_id, created_at) faz o índice cobrir filtro + ordenação,
 * sem precisar de filesort.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacoes_pontos', function (Blueprint $table) {
            $table->index(['cliente_id', 'created_at'], 'tp_cliente_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transacoes_pontos', function (Blueprint $table) {
            $table->dropIndex('tp_cliente_created_idx');
        });
    }
};
