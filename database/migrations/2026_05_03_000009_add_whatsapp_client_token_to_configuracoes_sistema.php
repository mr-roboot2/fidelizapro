<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Z-API exige um Client-Token (account-level) no header, distinto do
 * token da instância. Adiciona campo dedicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->string('whatsapp_client_token')->nullable()->after('whatsapp_api_token');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn('whatsapp_client_token');
        });
    }
};
