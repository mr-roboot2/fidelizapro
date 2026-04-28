<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->enum('whatsapp_provider', ['mock', 'evolution', 'zapi', 'meta_cloud'])->default('mock')->after('pdv_secret');
            $table->string('whatsapp_api_url')->nullable()->after('whatsapp_provider');
            $table->string('whatsapp_api_token')->nullable()->after('whatsapp_api_url');
            $table->string('whatsapp_instance')->nullable()->after('whatsapp_api_token');
            $table->string('whatsapp_phone_id')->nullable()->after('whatsapp_instance');
            $table->boolean('whatsapp_ativo')->default(false)->after('whatsapp_phone_id');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_provider', 'whatsapp_api_url', 'whatsapp_api_token',
                'whatsapp_instance', 'whatsapp_phone_id', 'whatsapp_ativo',
            ]);
        });
    }
};
