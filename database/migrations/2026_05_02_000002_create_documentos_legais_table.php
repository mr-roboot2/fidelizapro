<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_legais', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // privacidade, termos
            $table->string('titulo');
            $table->longText('conteudo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_legais');
    }
};
