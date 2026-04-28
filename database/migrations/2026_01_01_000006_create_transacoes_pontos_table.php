<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes_pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['credito', 'debito', 'expiracao', 'ajuste']);
            $table->enum('origem', ['compra', 'resgate', 'manual', 'indicacao', 'aniversario', 'cadastro', 'expiracao']);
            $table->decimal('pontos', 10, 2);
            $table->decimal('saldo_anterior', 12, 2);
            $table->decimal('saldo_posterior', 12, 2);
            $table->morphs('referencia');
            $table->string('descricao')->nullable();
            $table->date('expira_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes_pontos');
    }
};
