<?php

namespace App\Services\Pagamento;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Empresa;

interface GatewayInterface
{
    /**
     * Cria/sincroniza o customer no gateway.
     * Retorna gateway_customer_id.
     */
    public function criarCustomer(Empresa $empresa): string;

    /**
     * Cria assinatura no gateway. Retorna gateway_subscription_id.
     */
    public function criarAssinatura(Assinatura $assinatura): string;

    /**
     * Gera uma cobrança individual (link de pagamento). Atualiza o modelo.
     */
    public function gerarCobranca(Cobranca $cobranca): Cobranca;

    /**
     * Cancela a assinatura no gateway.
     */
    public function cancelarAssinatura(Assinatura $assinatura): bool;

    /**
     * Processa payload de webhook do gateway. Retorna ['evento'=>str, 'cobranca_id'=>int|null].
     */
    public function processarWebhook(array $payload): array;
}
