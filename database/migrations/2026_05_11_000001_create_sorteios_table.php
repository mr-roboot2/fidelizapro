<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorteios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('imagem')->nullable();
            $table->foreignId('recompensa_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('valor_estimado', 10, 2)->nullable();
            $table->date('data_sorteio');
            $table->enum('status', ['planejado', 'ativo', 'sorteado', 'cancelado'])->default('planejado');
            $table->unsignedSmallInteger('max_bilhetes_por_cliente')->nullable();
            $table->foreignId('vencedor_cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->unsignedBigInteger('vencedor_bilhete_id')->nullable();
            $table->timestamp('sorteado_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorteios');
    }
};
