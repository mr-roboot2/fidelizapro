<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleta_gatilhos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleta_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', [
                'primeiro_cadastro',
                'aniversario',
                'indicacao',
                'compra_acima',
                'inativo_dias',
                'atingiu_pontos',
            ]);
            $table->unsignedInteger('valor')->nullable();
            $table->unsignedTinyInteger('giros')->default(1);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['roleta_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleta_gatilhos');
    }
};
