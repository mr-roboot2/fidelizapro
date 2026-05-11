<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            // CSV com dias — ex: "3,1,0" envia aviso 3 dias antes, 1 dia antes, no dia
            $table->string('cobranca_avisos_antes', 60)->default('3,1,0')->after('pix_ativo');
            $table->string('cobranca_avisos_depois', 60)->default('1,7,15,30')->after('cobranca_avisos_antes');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn(['cobranca_avisos_antes', 'cobranca_avisos_depois']);
        });
    }
};
