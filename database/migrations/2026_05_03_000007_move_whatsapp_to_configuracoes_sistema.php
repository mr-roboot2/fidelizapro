<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Centraliza a configuração de WhatsApp na tabela configuracoes_sistema
 * (singleton do super admin). Todas as empresas passam a usar a mesma
 * integração — coerente com SaaS que tem uma Meta account única.
 *
 * As colunas equivalentes em empresas ficam (não dropamos) pra evitar
 * perda de dados durante a transição. Se quiser limpar depois, é só
 * dropar manualmente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->enum('whatsapp_provider', ['mock', 'evolution', 'zapi', 'meta_cloud'])
                  ->default('mock')->after('rodape_html');
            $table->string('whatsapp_api_url')->nullable()->after('whatsapp_provider');
            $table->text('whatsapp_api_token')->nullable()->after('whatsapp_api_url');
            $table->string('whatsapp_instance')->nullable()->after('whatsapp_api_token');
            $table->string('whatsapp_phone_id')->nullable()->after('whatsapp_instance');
            $table->string('whatsapp_waba_id', 50)->nullable()->after('whatsapp_phone_id');
            $table->string('whatsapp_webhook_verify_token', 64)->nullable()->after('whatsapp_waba_id');
            $table->boolean('whatsapp_ativo')->default(false)->after('whatsapp_webhook_verify_token');
        });

        // Copia da primeira empresa com whatsapp_ativo=true, se houver
        $empresa = DB::table('empresas')
            ->where('whatsapp_ativo', true)
            ->orderBy('id')
            ->first();

        // Se não tiver ativa, pega qualquer uma com config preenchida
        if (!$empresa) {
            $empresa = DB::table('empresas')
                ->whereNotNull('whatsapp_api_token')
                ->orderBy('id')
                ->first();
        }

        $config = DB::table('configuracoes_sistema')->where('id', 1)->first();
        if (!$config) {
            DB::table('configuracoes_sistema')->insert([
                'id' => 1,
                'nome_sistema' => 'FidelizaPro',
                'cor_primaria' => '#6366f1',
                'cor_secundaria' => '#8b5cf6',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($empresa) {
            DB::table('configuracoes_sistema')->where('id', 1)->update([
                'whatsapp_provider' => $empresa->whatsapp_provider ?? 'mock',
                'whatsapp_api_url'  => $empresa->whatsapp_api_url,
                'whatsapp_api_token' => $empresa->whatsapp_api_token,
                'whatsapp_instance' => $empresa->whatsapp_instance,
                'whatsapp_phone_id' => $empresa->whatsapp_phone_id,
                'whatsapp_waba_id'  => $empresa->whatsapp_waba_id,
                'whatsapp_webhook_verify_token' => $empresa->whatsapp_webhook_verify_token
                    ?? 'wh_'.\Illuminate\Support\Str::random(32),
                'whatsapp_ativo' => $empresa->whatsapp_ativo,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('configuracoes_sistema')->where('id', 1)->update([
                'whatsapp_webhook_verify_token' => 'wh_'.\Illuminate\Support\Str::random(32),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_provider', 'whatsapp_api_url', 'whatsapp_api_token',
                'whatsapp_instance', 'whatsapp_phone_id', 'whatsapp_waba_id',
                'whatsapp_webhook_verify_token', 'whatsapp_ativo',
            ]);
        });
    }
};
