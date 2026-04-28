<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained()->nullOnDelete();
            $table->string('acao', 30);                // created, updated, deleted, login, logout, custom
            $table->string('entidade')->nullable();    // App\Models\Cliente, etc
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->string('descricao')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entidade', 'entidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
