<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            // Token compartilhado com o painel Asaas (header asaas-access-token).
            // 500 chars cabe o valor criptografado pelo cast 'encrypted' do Laravel.
            $table->string('asaas_webhook_token', 500)->nullable()->after('pix_ativo');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn('asaas_webhook_token');
        });
    }
};
