<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roletas', function (Blueprint $table) {
            // Placeholders: {pontos}, {premio}, {primeiro_nome}
            $table->string('mensagem_pontos', 255)
                ->default('Você ganhou {pontos} pontos! 🎉')
                ->after('mensagem_consolacao');
            $table->string('mensagem_recompensa', 255)
                ->default('Você ganhou: {premio}! 🎁')
                ->after('mensagem_pontos');
            $table->string('mensagem_nova_chance', 255)
                ->default('Boa, {primeiro_nome}! Você ganhou um giro extra! 🎰')
                ->after('mensagem_recompensa');
        });
    }

    public function down(): void
    {
        Schema::table('roletas', function (Blueprint $table) {
            $table->dropColumn(['mensagem_pontos', 'mensagem_recompensa', 'mensagem_nova_chance']);
        });
    }
};
