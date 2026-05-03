<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('nome_sistema')->default('FidelizaPro');
            $table->string('slogan')->nullable();
            $table->string('logo')->nullable();          // path em storage/app/public
            $table->string('favicon')->nullable();
            $table->string('email_suporte')->nullable();
            $table->string('telefone_suporte', 30)->nullable();
            $table->string('whatsapp_suporte', 30)->nullable();
            $table->string('razao_social')->nullable();
            $table->string('cnpj', 20)->nullable();
            $table->string('endereco')->nullable();
            $table->string('cor_primaria', 7)->default('#6366f1');
            $table->string('cor_secundaria', 7)->default('#8b5cf6');
            $table->string('site_url')->nullable();      // ex: https://satisfy.com.br
            $table->text('rodape_html')->nullable();     // HTML pequeno pro footer público
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_sistema');
    }
};
