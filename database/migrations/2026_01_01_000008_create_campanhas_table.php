<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->text('mensagem');
            $table->enum('canal', ['whatsapp', 'sms', 'email'])->default('whatsapp');
            $table->enum('segmento', ['todos', 'aniversariantes', 'inativos', 'vips', 'sem_compra_30d', 'personalizado'])->default('todos');
            $table->json('filtros')->nullable();
            $table->enum('status', ['rascunho', 'agendada', 'enviando', 'concluida', 'falhou'])->default('rascunho');
            $table->timestamp('agendada_para')->nullable();
            $table->timestamp('enviada_em')->nullable();
            $table->integer('total_destinatarios')->default(0);
            $table->integer('total_enviados')->default(0);
            $table->integer('total_falhas')->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });

        Schema::create('campanha_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campanha_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pendente', 'enviado', 'entregue', 'lido', 'falhou'])->default('pendente');
            $table->timestamp('enviado_em')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanha_envios');
        Schema::dropIfExists('campanhas');
    }
};
