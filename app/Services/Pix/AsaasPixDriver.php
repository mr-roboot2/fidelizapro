<?php

namespace App\Services\Pix;

use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Driver pra Asaas (asaas.com). Doc: https://docs.asaas.com/
 * Fluxo: garante customer → cria payment PIX → busca QR Code.
 */
class AsaasPixDriver implements PixDriverInterface
{
    private function baseUrl(): string
    {
        $cfg = ConfiguracaoSistema::instancia();
        return $cfg->pix_ambiente === 'producao'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    private function client()
    {
        $cfg = ConfiguracaoSistema::instancia();
        $key = $cfg->pix_api_key;
        if (!$key) throw new RuntimeException('Asaas API key não configurada');

        return Http::baseUrl($this->baseUrl())
            ->withHeaders(['access_token' => $key, 'Content-Type' => 'application/json'])
            ->acceptJson()
            ->timeout(15);
    }

    public function gerarPix(Cobranca $cobranca, Empresa $empresa): array
    {
        $customerId = $this->garantirCustomer($empresa);

        // 1. Cria payment PIX
        $r = $this->client()->post('/payments', [
            'customer'    => $customerId,
            'billingType' => 'PIX',
            'value'       => (float) $cobranca->valor,
            'dueDate'     => $cobranca->vencimento->format('Y-m-d'),
            'description' => "Cobrança #{$cobranca->id} — assinatura FidelizaPro",
            'externalReference' => (string) $cobranca->id,
        ]);
        if (!$r->successful()) {
            throw new RuntimeException('Falha ao criar cobrança Asaas: '.$r->body());
        }
        $payment = $r->json();

        // 2. Busca QR Code — falha aqui é degradação parcial (ex.: conta sandbox
        // sem chave PIX cadastrada). Mantemos link_pagamento + payment id pra
        // que o lojista ainda consiga pagar via checkout web do Asaas.
        $qr = $this->client()->get("/payments/{$payment['id']}/pixQrCode");
        $qrData = [];
        if ($qr->successful()) {
            $qrData = $qr->json();
        } else {
            Log::warning('[Asaas] Falha ao gerar QR PIX — payment criado, link salvo', [
                'payment_id' => $payment['id'],
                'body'       => $qr->body(),
            ]);
        }

        return [
            'qr_code_base64'      => $qrData['encodedImage'] ?? null,
            'copia_cola'          => $qrData['payload'] ?? null,
            'expira_em'           => $qrData['expirationDate'] ?? null,
            'gateway_charge_id'   => $payment['id'],
            'gateway_customer_id' => $customerId,
            'link_pagamento'      => $payment['invoiceUrl'] ?? null,
        ];
    }

    /**
     * Reusa o gateway_customer_id da assinatura ou cria via API.
     */
    private function garantirCustomer(Empresa $empresa): string
    {
        $a = $empresa->assinatura;
        if ($a && $a->gateway_customer_id && str_starts_with($a->gateway_customer_id, 'cus_')) {
            return $a->gateway_customer_id;
        }

        $r = $this->client()->post('/customers', [
            'name'     => $empresa->nome,
            'email'    => $empresa->email,
            'cpfCnpj'  => preg_replace('/\D/', '', (string) $empresa->cnpj) ?: null,
            'mobilePhone' => preg_replace('/\D/', '', (string) $empresa->telefone) ?: null,
            'externalReference' => 'empresa-'.$empresa->id,
        ]);
        if (!$r->successful()) {
            throw new RuntimeException('Falha ao criar customer Asaas: '.$r->body());
        }
        $id = $r->json('id');
        if ($a) $a->update(['gateway_customer_id' => $id]);
        return $id;
    }

    public function processarWebhook(array $payload): ?Cobranca
    {
        // Asaas envia { event: "PAYMENT_RECEIVED", payment: { id, ... } }
        $evento = $payload['event'] ?? null;
        $paymentId = $payload['payment']['id'] ?? null;
        if (!$paymentId) return null;
        if (!in_array($evento, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true)) return null;
        return Cobranca::where('gateway_charge_id', $paymentId)->first();
    }
}
