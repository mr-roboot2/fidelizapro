<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_indicador_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cliente_indicado_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('telefone_indicado', 20);
            $table->string('nome_indicado')->nullable();
            $table->enum('status', ['pendente', 'cadastrado', 'convertido', 'expirado'])->default('pendente');
            $table->decimal('pontos_concedidos', 10, 2)->default(0);
            $table->timestamp('convertida_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicacoes');
    }
};
