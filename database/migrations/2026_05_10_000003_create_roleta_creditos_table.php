<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleta_creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('giros_disponiveis')->default(0);
            $table->enum('origem', ['manual', 'primeiro_cadastro', 'consolacao'])->default('manual');
            $table->timestamp('expira_em')->nullable();
            $table->timestamps();

            $table->unique(['roleta_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleta_creditos');
    }
};
