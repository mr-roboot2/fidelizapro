<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->enum('pix_provider', ['mock', 'asaas'])->default('mock')->after('whatsapp_ativo');
            $table->enum('pix_ambiente', ['producao', 'sandbox'])->default('sandbox')->after('pix_provider');
            $table->text('pix_api_key')->nullable()->after('pix_ambiente');
            $table->string('pix_webhook_token', 64)->nullable()->after('pix_api_key');
            $table->boolean('pix_ativo')->default(false)->after('pix_webhook_token');
        });

        // Gera token único pro webhook (pra validar origem)
        DB::table('configuracoes_sistema')->update([
            'pix_webhook_token' => Str::random(48),
        ]);
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn([
                'pix_provider', 'pix_ambiente', 'pix_api_key',
                'pix_webhook_token', 'pix_ativo',
            ]);
        });
    }
};
