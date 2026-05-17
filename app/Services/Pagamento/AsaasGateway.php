<?php

namespace App\Services\Pagamento;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Asaas (asaas.com) — gateway brasileiro popular para SaaS.
 * Docs: https://docs.asaas.com/
 *
 * Configuração: super-admin → Configurações → Integrações → PIX
 *   - Provider: asaas
 *   - Ambiente: sandbox / producao
 *   - API key
 * Fallback: .env (ASAAS_API_KEY, ASAAS_ENV) — só usado se a config
 * do banco estiver vazia (útil pra ambientes sem painel ainda).
 */
class AsaasGateway implements GatewayInterface
{
    protected function baseUrl(): string
    {
        $cfg = ConfiguracaoSistema::instancia();
        // config('services.asaas.env') em vez de env() direto. env() fora
        // de config/ retorna null quando config:cache está ativo
        // (php-fpm não tem $_ENV populado) — pagamento Asaas quebrava
        // silenciosamente em produção com cache.
        $ambiente = $cfg->pix_ambiente ?: config('services.asaas.env', 'sandbox');
        return $ambiente === 'producao'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    protected function http()
    {
        $cfg = ConfiguracaoSistema::instancia();
        $key = $cfg->pix_api_key ?: config('services.asaas.api_key');
        if (!$key) {
            throw new RuntimeException(
                'Chave Asaas não configurada. Vá em Configurações → Integrações → PIX e cadastre a API key.'
            );
        }
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
            throw new RuntimeException('Asaas: falha ao criar customer: '.\App\Support\LogScrubber::scrub($r->body()));
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
            throw new RuntimeException('Asaas: falha ao criar assinatura: '.\App\Support\LogScrubber::scrub($r->body()));
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
            throw new RuntimeException('Asaas: falha ao gerar cobrança: '.\App\Support\LogScrubber::scrub($r->body()));
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
        // Sem gateway_subscription_id, a URL ficava /subscriptions/ (sem
        // id) e o DELETE batia em endpoint genérico — comportamento
        // indefinido. Considera "cancelado com sucesso" pq não há nada
        // no gateway pra cancelar (assinatura local sem espelhamento).
        if (empty($assinatura->gateway_subscription_id)) {
            return true;
        }
        $r = $this->http()->delete($this->baseUrl().'/subscriptions/'.$assinatura->gateway_subscription_id);
        return $r->successful();
    }

    /**
     * Cancela uma cobrança individual no Asaas (DELETE /payments/{id}).
     * Só funciona pra cobranças não pagas. Retorna true se cancelou ou se nem
     * existia gateway_charge_id pra cancelar.
     */
    public function cancelarCobranca(Cobranca $cobranca): bool
    {
        if (!$cobranca->gateway_charge_id) return true;
        $r = $this->http()->delete($this->baseUrl().'/payments/'.$cobranca->gateway_charge_id);
        if (!$r->successful()) {
            Log::warning('Asaas: falha ao cancelar cobrança', [
                'cobranca_id' => $cobranca->id,
                'gateway_id'  => $cobranca->gateway_charge_id,
                'body'        => \App\Support\LogScrubber::scrub($r->body()),
            ]);
            return false;
        }
        return true;
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
