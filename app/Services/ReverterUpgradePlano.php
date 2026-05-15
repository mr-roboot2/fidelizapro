<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\Empresa;

/**
 * Se a cobrança foi criada por um upgrade de plano (tem meta.upgrade
 * com snapshot anterior), reverte Assinatura e Empresa pros valores
 * antigos. Usado quando o lojista cancela/exclui a cobrança antes
 * de pagar — evita que ele fique com plano novo sem ter pago.
 *
 * Retorna true se reverteu, false se a cobrança não era de upgrade.
 */
class ReverterUpgradePlano
{
    public function executar(Cobranca $cobranca): bool
    {
        $snap = $cobranca->meta['upgrade'] ?? null;
        if (!$snap) return false;

        $assinatura = $cobranca->assinatura;
        if ($assinatura) {
            // Se a assinatura foi criada nesse upgrade (não existia antes),
            // não dá pra "reverter" — só zerar o plano alvo. Mantemos a
            // assinatura, mas voltando pra null/snapshot.
            $assinatura->update([
                'plano_id'           => $snap['plano_anterior_id'] ?? $assinatura->plano_id,
                'valor_mensal'       => $snap['valor_mensal_anterior'] ?? 0,
                'proximo_vencimento' => $snap['proximo_vencimento_anterior'] ?? null,
            ]);
        }

        $empresa = Empresa::find($cobranca->empresa_id);
        if ($empresa) {
            $empresa->update([
                'plano_id' => $snap['empresa_plano_id_anterior'] ?? null,
            ]);
        }

        return true;
    }
}
