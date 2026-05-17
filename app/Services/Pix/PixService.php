<?php

namespace App\Services\Pix;

use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
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
     * Se a cobrança for de upgrade de plano, efetiva o upgrade aqui.
     *
     * Race fix: dois webhooks paralelos pro mesmo charge_id (Asaas faz retry
     * agressivo) liam status='pendente' simultâneo e aplicavam upgrade 2x.
     * Mesmo padrão do AssinaturaService::marcarPaga — lockForUpdate no
     * Cobranca + early-return idempotente. A Assinatura também é lockada e
     * o proximo_vencimento usa max(atual, candidato) pra preservar
     * monotonicidade (webhook atrasado de cobrança antiga não retroage o
     * vencimento — bug que existia aqui mas não em AssinaturaService).
     */
    public function confirmarPagamento(Cobranca $cobranca): void
    {
        DB::transaction(function () use ($cobranca) {
            $lockada = Cobranca::lockForUpdate()->find($cobranca->id);
            // Só transiciona de 'pendente' pra 'pago'. Webhook PIX atrasado
            // pra cobrança já cancelada/estornada NÃO deve reativar a
            // cobrança — mesmo padrão de AssinaturaService::marcarPaga.
            if (!$lockada || $lockada->status !== 'pendente') {
                return;
            }

            $lockada->update([
                'status'  => 'pago',
                'pago_em' => now(),
            ]);

            // Efetiva upgrade pendente antes de calcular próximo vencimento,
            // pra que o update da assinatura (valor_mensal, etc.) pegue.
            (new \App\Services\AplicarUpgradePlano())->executar($lockada->fresh());

            // Lock + monotonicidade. Sem o lock, 2 webhooks de cobranças
            // distintas da mesma assinatura avançam só 1 mês ao invés de 2.
            // Sem max(), webhook PIX de cobrança vencida (atrasado) reescreve
            // o vencimento pra now+1m, encurtando o ciclo já ativo do cliente.
            $assinatura = \App\Models\Assinatura::lockForUpdate()->find($lockada->fresh()->assinatura_id);
            if ($assinatura) {
                $base = $lockada->vencimento ?? now();
                $candidato = $base->copy()->addMonth();
                $atual = $assinatura->proximo_vencimento;
                $novoVencimento = ($atual && $atual->gt($candidato)) ? $atual : $candidato;
                $assinatura->update([
                    'status'             => 'ativa',
                    'proximo_vencimento' => $novoVencimento,
                ]);
            }
        });
    }
}
