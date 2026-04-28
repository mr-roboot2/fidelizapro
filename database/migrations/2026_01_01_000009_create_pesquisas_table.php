<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesquisas_satisfacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compra_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('nota');
            $table->text('comentario')->nullable();
            $table->json('respostas')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'nota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesquisas_satisfacao');
    }
};
