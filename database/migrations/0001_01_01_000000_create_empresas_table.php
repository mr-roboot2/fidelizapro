<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cnpj', 18)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();
            $table->string('logo')->nullable();
            $table->string('cor_primaria', 7)->default('#6366f1');
            $table->string('cor_secundaria', 7)->default('#8b5cf6');
            $table->decimal('pontos_por_real', 8, 2)->default(1.00);
            $table->decimal('cashback_percentual', 5, 2)->default(0.00);
            $table->integer('validade_pontos_dias')->default(365);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
