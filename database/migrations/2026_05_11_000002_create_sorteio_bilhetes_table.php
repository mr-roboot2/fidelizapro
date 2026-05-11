<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorteio_bilhetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sorteio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->enum('origem', ['roleta', 'compra', 'manual', 'consolacao'])->default('roleta');
            $table->string('referencia', 80)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sorteio_id', 'cliente_id']);
        });

        // FK do vencedor_bilhete_id agora que a tabela existe
        Schema::table('sorteios', function (Blueprint $table) {
            $table->foreign('vencedor_bilhete_id')->references('id')->on('sorteio_bilhetes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sorteios', function (Blueprint $table) {
            $table->dropForeign(['vencedor_bilhete_id']);
        });
        Schema::dropIfExists('sorteio_bilhetes');
    }
};
