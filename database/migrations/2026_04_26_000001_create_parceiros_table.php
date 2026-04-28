<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parceiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('slug');
            $table->text('descricao')->nullable();
            $table->string('categoria')->nullable();
            $table->string('logo')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();
            $table->string('site')->nullable();
            $table->string('validacao_secret', 32)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'slug']);
            $table->index(['empresa_id', 'ativo']);
        });

        Schema::create('beneficios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parceiro_id')->constrained('parceiros')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['desconto_percentual', 'desconto_valor', 'brinde', 'servico_gratis', 'cortesia']);
            $table->decimal('valor', 10, 2)->nullable();
            $table->text('condicoes')->nullable();
            $table->date('valido_ate')->nullable();
            $table->integer('limite_por_cliente')->nullable();
            $table->integer('limite_total')->nullable();
            $table->integer('total_resgatados')->default(0);
            $table->boolean('destaque')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['parceiro_id', 'ativo']);
        });

        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficio_id')->constrained('beneficios')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('codigo', 12)->unique();
            $table->enum('status', ['disponivel', 'usado', 'expirado'])->default('disponivel');
            $table->dateTime('valido_ate');
            $table->dateTime('usado_em')->nullable();
            $table->text('observacao_uso')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'status']);
            $table->index(['beneficio_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupons');
        Schema::dropIfExists('beneficios');
        Schema::dropIfExists('parceiros');
    }
};
