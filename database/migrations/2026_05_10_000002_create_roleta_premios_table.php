<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleta_premios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleta_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('ordem')->default(0);
            $table->string('label', 60);
            $table->string('cor', 7)->default('#6366f1');
            $table->enum('tipo', ['recompensa', 'pontos', 'nova_chance', 'nada']);
            $table->foreignId('recompensa_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('pontos')->nullable();
            $table->unsignedSmallInteger('peso')->default(10);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['roleta_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleta_premios');
    }
};
