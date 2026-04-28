<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', [
                'boas_vindas',           // imediato após cadastro
                'aniversario',           // no dia do aniversário
                'pontos_vencendo',       // X dias antes do vencimento
                'inativo_30d',           // 30 dias sem comprar
                'inativo_60d',           // 60 dias sem comprar
                'pos_compra',            // X horas após compra
                'agradecimento_resgate', // após resgate aprovado
            ]);
            $table->string('nome');
            $table->text('mensagem');
            $table->integer('dias_offset')->default(0);
            $table->boolean('ativo')->default(true);
            $table->dateTime('ultima_execucao')->nullable();
            $table->integer('total_enviados')->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'tipo']);
            $table->index(['empresa_id', 'ativo']);
        });

        Schema::create('automacao_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automacao_id')->constrained('automacoes')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->boolean('sucesso');
            $table->text('mensagem_enviada')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();

            $table->index(['automacao_id', 'cliente_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automacao_logs');
        Schema::dropIfExists('automacoes');
    }
};
