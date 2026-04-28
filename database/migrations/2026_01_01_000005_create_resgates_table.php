<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resgates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recompensa_id')->constrained()->restrictOnDelete();
            $table->string('codigo', 12)->unique();
            $table->integer('pontos_usados');
            $table->enum('status', ['pendente', 'aprovado', 'entregue', 'cancelado'])->default('pendente');
            $table->text('observacao')->nullable();
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resgates');
    }
};
