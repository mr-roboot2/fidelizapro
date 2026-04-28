<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('codigo')->nullable();
            $table->decimal('valor', 10, 2);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('pontos_gerados', 10, 2)->default(0);
            $table->decimal('cashback_gerado', 10, 2)->default(0);
            $table->string('descricao')->nullable();
            $table->enum('origem', ['manual', 'pdv', 'app'])->default('manual');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
