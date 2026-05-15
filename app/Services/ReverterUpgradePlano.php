<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\Empresa;

/**
 * Reverte um upgrade pendente quando a cobrança é cancelada/excluída.
 *
 * Formato novo (a partir de 2026-05-14): meta.upgrade = { plano_alvo_id }
 *   → o plano ainda não foi mudado; basta zerar plano_id_pendente.
 *
 * Formato antigo: meta.upgrade = { plano_anterior_id, valor_mensal_anterior,
 * proximo_vencimento_anterior, empresa_plano_id_anterior }
 *   → o plano foi mudado prematuramente; reverter Assinatura + Empresa.
 *
 * Retorna true se a cobrança era de upgrade (e reverteu), false caso contrário.
 */
class ReverterUpgradePlano
{
    public function executar(Cobranca $cobranca): bool
    {
        $snap = $cobranca->meta['upgrade'] ?? null;
        if (!$snap) return false;

        $assinatura = $cobranca->assinatura;

        // Formato novo: só zera plano_id_pendente
        if (isset($snap['plano_alvo_id']) && !isset($snap['plano_anterior_id'])) {
            if ($assinatura) {
                $assinatura->update(['plano_id_pendente' => null]);
            }
            return true;
        }

        // Formato antigo (compat): reverte snapshot completo
        if ($assinatura) {
            $assinatura->update([
                'plano_id'           => $snap['plano_anterior_id'] ?? $assinatura->plano_id,
                'plano_id_pendente'  => null,
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
