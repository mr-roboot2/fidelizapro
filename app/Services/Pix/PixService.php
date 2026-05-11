<?php

namespace App\Services\Pix;

use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Throwable;

class PixService
{
    public function driver(): PixDriverInterface
    {
        $cfg = ConfiguracaoSistema::instancia();
        if (!$cfg->pix_ativo) return new MockPixDriver();

        return match ($cfg->pix_provider) {
            'asaas' => new AsaasPixDriver(),
            default => new MockPixDriver(),
        };
    }

    /**
     * Gera PIX pra cobrança e persiste em cobranca.meta + atualiza
     * link_pagamento. Idempotente: se já tem dados PIX válidos, retorna.
     */
    public function gerarParaCobranca(Cobranca $cobranca, Empresa $empresa): Cobranca
    {
        $meta = $cobranca->meta ?? [];
        if (!empty($meta['pix_copia_cola']) && $cobranca->gateway_charge_id) {
            return $cobranca;
        }

        try {
            $resultado = $this->driver()->gerarPix($cobranca, $empresa);

            $meta = array_merge($meta, [
                'pix_qr_code'     => $resultado['qr_code_base64'] ?? null, // Asaas devolve PNG base64
                'pix_qr_code_svg' => $resultado['qr_code_svg'] ?? null,    // Mock devolve SVG inline
                'pix_copia_cola'  => $resultado['copia_cola'] ?? null,
                'pix_expira_em'   => $resultado['expira_em'] ?? null,
            ]);

            $cobranca->update([
                'forma_pagamento'    => 'pix',
                'gateway_charge_id'  => $resultado['gateway_charge_id'] ?? null,
                'link_pagamento'     => $resultado['link_pagamento'] ?? null,
                'meta'               => $meta,
            ]);
        } catch (Throwable $e) {
            Log::warning('[PIX] Falha ao gerar cobrança: '.$e->getMessage(), [
                'cobranca_id' => $cobranca->id,
                'empresa_id'  => $empresa->id,
            ]);
        }

        return $cobranca->fresh();
    }

    /**
     * Marca a cobrança como paga, atualiza a assinatura (próximo vencimento
     * +30 dias) e desliga inadimplência. Chamado pelo webhook do gateway.
     */
    public function confirmarPagamento(Cobranca $cobranca): void
    {
        if ($cobranca->status === 'pago') return;

        $cobranca->update([
            'status'  => 'pago',
            'pago_em' => now(),
        ]);

        $assinatura = $cobranca->assinatura;
        if ($assinatura) {
            $venc = $assinatura->proximo_vencimento && $assinatura->proximo_vencimento->isFuture()
                ? $assinatura->proximo_vencimento->copy()->addMonth()
                : now()->addMonth();
            $assinatura->update([
                'status'             => 'ativa',
                'proximo_vencimento' => $venc,
            ]);
        }
    }
}
