<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captcha configurável via painel super admin (e não só via env).
 * Mesmo padrão das outras credenciais sensíveis em
 * configuracoes_sistema (asaas_webhook_token, pix_api_key, etc.):
 *   - provider em texto plain
 *   - site_key em texto plain (já é pública)
 *   - secret_key cifrada (cast 'encrypted' no model)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->string('captcha_provider', 20)->default('disabled')->after('whatsapp_app_secret');
            $table->string('captcha_site_key', 200)->nullable()->after('captcha_provider');
            $table->string('captcha_secret_key', 500)->nullable()->after('captcha_site_key');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn(['captcha_provider', 'captcha_site_key', 'captcha_secret_key']);
        });
    }
};
