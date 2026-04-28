<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plano_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['trial', 'ativa', 'inadimplente', 'cancelada', 'pausada'])->default('trial');
            $table->enum('gateway', ['mock', 'asaas', 'stripe', 'mercado_pago'])->default('mock');
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->decimal('valor_mensal', 10, 2);
            $table->date('inicio');
            $table->date('proximo_vencimento')->nullable();
            $table->date('cancelada_em')->nullable();
            $table->date('trial_ate')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index('proximo_vencimento');
        });

        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assinatura_id')->constrained()->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->date('vencimento');
            $table->dateTime('pago_em')->nullable();
            $table->enum('status', ['pendente', 'pago', 'vencido', 'cancelado', 'estornado'])->default('pendente');
            $table->string('gateway_charge_id')->nullable();
            $table->string('link_pagamento')->nullable();
            $table->string('forma_pagamento')->nullable();    // pix, boleto, cartao
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['assinatura_id', 'status']);
            $table->index(['vencimento', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas');
        Schema::dropIfExists('assinaturas');
    }
};
