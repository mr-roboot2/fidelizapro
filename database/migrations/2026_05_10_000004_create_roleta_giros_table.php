<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleta_giros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('roleta_premio_id')->nullable()->constrained('roleta_premios')->nullOnDelete();
            $table->enum('tipo_resultado', ['recompensa', 'pontos', 'nova_chance', 'consolacao']);
            $table->unsignedInteger('pontos_concedidos')->nullable();
            $table->foreignId('recompensa_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resgate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('executado_em')->useCurrent();
            $table->timestamps();

            $table->index(['roleta_id', 'cliente_id']);
            $table->index(['cliente_id', 'executado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleta_giros');
    }
};
