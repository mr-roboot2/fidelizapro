<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('telefone', 20);
            $table->string('email')->nullable();
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('password')->nullable();
            $table->string('codigo_qr')->unique()->nullable();
            $table->string('codigo_indicacao', 10)->unique()->nullable();
            $table->foreignId('indicado_por_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->decimal('pontos_atual', 12, 2)->default(0);
            $table->decimal('cashback_atual', 12, 2)->default(0);
            $table->decimal('total_gasto', 12, 2)->default(0);
            $table->integer('total_compras')->default(0);
            $table->timestamp('ultimo_acesso')->nullable();
            $table->timestamp('ultima_compra')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('aceita_whatsapp')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'telefone']);
            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
