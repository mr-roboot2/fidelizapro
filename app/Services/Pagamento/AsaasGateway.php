<?php

namespace App\Services\Pagamento;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Asaas (asaas.com) — gateway brasileiro popular para SaaS.
 * Docs: https://docs.asaas.com/
 *
 * .env:
 *   ASAAS_API_KEY=...
 *   ASAAS_ENV=sandbox  // ou production
 */
class AsaasGateway implements GatewayInterface
{
    protected function baseUrl(): string
    {
        return env('ASAAS_ENV') === 'production'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    protected function http()
    {
        $key = env('ASAAS_API_KEY');
        if (!$key) throw new RuntimeException('ASAAS_API_KEY não configurada no .env');
        return Http::withHeaders([
            'access_token' => $key,
            'Content-Type' => 'application/json',
        ])->timeout(15);
    }

    public function criarCustomer(Empresa $empresa): string
    {
        $r = $this->http()->post($this->baseUrl().'/customers', [
            'name' => $empresa->nome,
            'cpfCnpj' => preg_replace('/\D/', '', $empresa->cnpj ?? ''),
            'email' => $empresa->email,
            'phone' => preg_replace('/\D/', '', $empresa->telefone ?? ''),
            'externalReference' => 'empresa_'.$empresa->id,
        ]);

        if (!$r->successful()) {
            throw new RuntimeException('Asaas: falha ao criar customer: '.$r->body());
        }

        return $r->json('id');
    }

    public function criarAssinatura(Assinatura $assinatura): string
    {
        $r = $this->http()->post($this->baseUrl().'/subscriptions', [
            'customer' => $assinatura->gateway_customer_id,
            'billingType' => 'PIX',
            'value' => (float) $assinatura->valor_mensal,
            'nextDueDate' => $assinatura->proximo_vencimento?->toDateString() ?? now()->addDays(7)->toDateString(),
            'cycle' => 'MONTHLY',
            'description' => 'FidelizaPro — '.$assinatura->plano->nome,
            'externalReference' => 'assinatura_'.$assinatura->id,
        ]);

        if (!$r->successful()) {
            throw new RuntimeException('Asaas: falha ao criar assinatura: '.$r->body());
        }

        return $r->json('id');
    }

    public function gerarCobranca(Cobranca $cobranca): Cobranca
    {
        $r = $this->http()->post($this->baseUrl().'/payments', [
            'customer' => $cobranca->assinatura->gateway_customer_id,
            'billingType' => 'PIX',
            'value' => (float) $cobranca->valor,
            'dueDate' => $cobranca->vencimento->toDateString(),
            'description' => 'Mensalidade FidelizaPro',
            'externalReference' => 'cobranca_'.$cobranca->id,
        ]);

        if (!$r->successful()) {
            throw new RuntimeException('Asaas: falha ao gerar cobrança: '.$r->body());
        }

        $cobranca->update([
            'gateway_charge_id' => $r->json('id'),
            'link_pagamento' => $r->json('invoiceUrl'),
            'forma_pagamento' => 'pix',
        ]);

        return $cobranca;
    }

    public function cancelarAssinatura(Assinatura $assinatura): bool
    {
        $r = $this->http()->delete($this->baseUrl().'/subscriptions/'.$assinatura->gateway_subscription_id);
        return $r->successful();
    }

    public function processarWebhook(array $payload): array
    {
        // Asaas webhook: { event: 'PAYMENT_RECEIVED', payment: { id, externalReference } }
        $evento = $payload['event'] ?? '';
        $cobrancaId = null;

        if (isset($payload['payment']['externalReference'])) {
            $ref = $payload['payment']['externalReference'];
            if (str_starts_with($ref, 'cobranca_')) {
                $cobrancaId = (int) substr($ref, 9);
            }
        }

        return [
            'evento' => $evento,
            'gateway_charge_id' => $payload['payment']['id'] ?? null,
            'cobranca_id' => $cobrancaId,
        ];
    }
}
