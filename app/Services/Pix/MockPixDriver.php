<?php

namespace App\Services\Pix;

use App\Models\Cobranca;
use App\Models\Empresa;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Generator as QrGenerator;

/**
 * Driver de desenvolvimento — gera um QR Code com texto fake (não funciona
 * pra pagar de verdade) e marca a cobrança automaticamente como paga após
 * alguns segundos via comando manual. Útil pra rodar o fluxo sem credenciais.
 */
class MockPixDriver implements PixDriverInterface
{
    public function gerarPix(Cobranca $cobranca, Empresa $empresa): array
    {
        $copiaCola = sprintf(
            'MOCK-PIX|empresa=%d|cobranca=%d|valor=%.2f|venc=%s',
            $empresa->id, $cobranca->id, $cobranca->valor, $cobranca->vencimento->format('Y-m-d')
        );

        // SVG não exige imagick/GD — funciona em qualquer PHP. Renderizado
        // inline na view; Asaas devolve PNG base64 e a view escolhe um dos dois.
        $svg = (new QrGenerator())->format('svg')->size(280)->margin(1)->generate($copiaCola);

        return [
            'qr_code_svg'         => (string) $svg,
            'copia_cola'          => $copiaCola,
            'expira_em'           => now()->addDays(7)->toIso8601String(),
            'gateway_charge_id'   => 'mock_'.Str::random(16),
            'gateway_customer_id' => 'mock_cus_'.$empresa->id,
        ];
    }

    public function processarWebhook(array $payload): ?Cobranca
    {
        $chargeId = $payload['charge_id'] ?? null;
        if (!$chargeId) return null;
        return Cobranca::where('gateway_charge_id', $chargeId)->first();
    }
}
