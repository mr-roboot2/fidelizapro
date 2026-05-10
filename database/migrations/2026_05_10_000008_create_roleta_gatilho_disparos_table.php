<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleta_gatilho_disparos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('referencia', 80);
            $table->unsignedTinyInteger('giros_creditados');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['roleta_id', 'cliente_id', 'referencia']);
            $table->index(['roleta_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleta_gatilho_disparos');
    }
};
