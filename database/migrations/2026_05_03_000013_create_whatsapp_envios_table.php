<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro central de todos os envios de WhatsApp feitos pelo sistema —
 * OTPs, eventos automáticos (boas-vindas, pos-compra, cashback liberado),
 * automações agendadas, campanhas e disparos manuais. Permite auditoria
 * e troubleshooting numa única tela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telefone', 20);
            $table->string('evento', 50)->default('livre');
            $table->string('origem', 30)->default('manual');
            $table->text('mensagem')->nullable();
            $table->string('provider', 20)->nullable();
            $table->boolean('sucesso')->default(false);
            $table->text('erro')->nullable();
            $table->string('external_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['cliente_id', 'created_at']);
            $table->index('telefone');
            $table->index(['evento', 'sucesso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_envios');
    }
};
