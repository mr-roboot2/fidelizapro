<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona `whatsapp_app_secret` em configuracoes_sistema.
 *
 * O Meta Cloud API assina cada webhook POST com HMAC SHA256 do payload
 * usando o "App Secret" do app Meta (diferente do verify_token). Sem isso,
 * qualquer um pode forjar webhook. Hoje o endpoint só loga, mas estamos
 * adicionando validação preventiva agora — quando passar a processar
 * eventos, a porta já está fechada.
 *
 * Cast `'encrypted'` em ConfiguracaoSistema mantém o segredo cifrado no DB
 * (mesma estratégia das outras credenciais sensíveis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->string('whatsapp_app_secret', 500)->nullable()->after('whatsapp_webhook_verify_token');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn('whatsapp_app_secret');
        });
    }
};
