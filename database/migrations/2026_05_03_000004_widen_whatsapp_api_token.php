<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens da Meta Cloud API podem ultrapassar 255 chars (System User Tokens
 * permanentes costumam ter 250-500). Trocar pra text resolve sem perder nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->text('whatsapp_api_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('whatsapp_api_token', 255)->nullable()->change();
        });
    }
};
