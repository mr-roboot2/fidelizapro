<?php

namespace App\Services\Pix;

use App\Models\Cobranca;
use App\Models\Empresa;

interface PixDriverInterface
{
    /**
     * Gera PIX pra cobrança. Retorna:
     *   [
     *     'qr_code_base64' => 'iVBORw0KGgo...',   // PNG do QR
     *     'copia_cola'     => '00020126...',     // string EMV
     *     'expira_em'      => '2026-05-18T23:59:59-03:00',
     *     'gateway_charge_id' => 'pay_xyz',
     *     'gateway_customer_id' => 'cus_xyz',
     *   ]
     */
    public function gerarPix(Cobranca $cobranca, Empresa $empresa): array;

    /**
     * Processa payload de webhook do gateway. Retorna a Cobranca afetada
     * ou null se o evento foi ignorado.
     */
    public function processarWebhook(array $payload): ?Cobranca;
}
