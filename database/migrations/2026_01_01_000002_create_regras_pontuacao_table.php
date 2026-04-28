<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_pontuacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->enum('tipo', ['compra', 'aniversario', 'indicacao', 'primeira_compra', 'cadastro']);
            $table->decimal('valor_minimo', 10, 2)->default(0);
            $table->decimal('valor_maximo', 10, 2)->nullable();
            $table->decimal('pontos_por_real', 8, 2)->default(1);
            $table->decimal('multiplicador', 5, 2)->default(1);
            $table->integer('pontos_fixos')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_pontuacao');
    }
};
