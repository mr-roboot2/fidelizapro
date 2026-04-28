<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recompensas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('imagem')->nullable();
            $table->integer('custo_pontos');
            $table->integer('estoque')->nullable();
            $table->integer('estoque_inicial')->nullable();
            $table->enum('tipo', ['produto', 'desconto', 'servico', 'experiencia'])->default('produto');
            $table->decimal('valor_estimado', 10, 2)->nullable();
            $table->boolean('destaque')->default(false);
            $table->boolean('ativo')->default(true);
            $table->date('valido_ate')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recompensas');
    }
};
