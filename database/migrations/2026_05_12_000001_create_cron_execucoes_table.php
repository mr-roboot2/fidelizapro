<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_execucoes', function (Blueprint $table) {
            $table->id();
            $table->string('comando', 120)->index();
            $table->timestamp('iniciado_em')->useCurrent();
            $table->timestamp('terminado_em')->nullable();
            $table->unsignedInteger('duracao_ms')->nullable();
            $table->enum('status', ['rodando', 'sucesso', 'falhou'])->default('rodando');
            $table->smallInteger('exit_code')->nullable();
            $table->text('output')->nullable();
            $table->text('erro')->nullable();
            $table->enum('origem', ['scheduler', 'manual', 'cli'])->default('cli');
            $table->timestamps();

            $table->index(['comando', 'iniciado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_execucoes');
    }
};
