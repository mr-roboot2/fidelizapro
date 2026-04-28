<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentos_cashback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['credito', 'debito', 'ajuste']);
            $table->enum('origem', ['compra', 'utilizacao', 'manual', 'estorno']);
            $table->decimal('valor', 10, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_posterior', 12, 2);
            $table->morphs('referencia');
            $table->string('descricao')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentos_cashback');
    }
};
