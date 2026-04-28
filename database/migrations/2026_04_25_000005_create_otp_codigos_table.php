<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codigos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('telefone', 20);
            $table->string('codigo', 6);
            $table->dateTime('expires_at');
            $table->boolean('usado')->default(false);
            $table->string('ip', 45)->nullable();
            $table->integer('tentativas')->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'telefone', 'usado']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codigos');
    }
};
