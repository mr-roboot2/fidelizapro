<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roletas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nome')->default('Roleta da Sorte');
            $table->boolean('ativa')->default(false);
            $table->enum('modo', ['porcentagem'])->default('porcentagem');
            $table->unsignedSmallInteger('tempo_min_ms')->default(3000);
            $table->unsignedSmallInteger('tempo_max_ms')->default(6000);
            $table->string('mensagem_consolacao', 255)->default('Não foi dessa vez, mas você ganhou {pontos} pontos pra continuar acumulando!');
            $table->unsignedTinyInteger('pontos_consolacao')->default(10);
            $table->unsignedTinyInteger('limite_giros_dia')->default(3);
            $table->timestamps();

            $table->unique('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roletas');
    }
};
