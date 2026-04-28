<?php

namespace App\Services\Pagamento;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mock para dev. Gera links fake e devolve respostas previsíveis.
 */
class MockGateway implements GatewayInterface
{
    public function criarCustomer(Empresa $empresa): string
    {
        $id = 'cus_mock_'.Str::random(16);
        Log::info("[Mock Pagamento] Customer criado: {$id} ({$empresa->nome})");
        return $id;
    }

    public function criarAssinatura(Assinatura $assinatura): string
    {
        $id = 'sub_mock_'.Str::random(16);
        Log::info("[Mock Pagamento] Assinatura criada: {$id} ({$assinatura->empresa->nome})");
        return $id;
    }

    public function gerarCobranca(Cobranca $cobranca): Cobranca
    {
        $cobranca->update([
            'gateway_charge_id' => 'chg_mock_'.Str::random(16),
            'link_pagamento' => url('/pagamento-mock/'.$cobranca->id),
            'forma_pagamento' => 'pix',
        ]);
        return $cobranca;
    }

    public function cancelarAssinatura(Assinatura $assinatura): bool
    {
        Log::info("[Mock Pagamento] Assinatura cancelada: {$assinatura->gateway_subscription_id}");
        return true;
    }

    public function processarWebhook(array $payload): array
    {
        // Simula webhook: { event: 'PAYMENT_RECEIVED', charge_id: 'chg_xxx' }
        return [
            'evento' => $payload['event'] ?? 'unknown',
            'gateway_charge_id' => $payload['charge_id'] ?? null,
        ];
    }
}
