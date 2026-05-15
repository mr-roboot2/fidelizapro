<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\Empresa;
use App\Models\Plano;

/**
 * Quando uma cobrança PAGA tem meta.upgrade.plano_alvo_id, efetiva o
 * upgrade: muda plano_id atual da Assinatura e da Empresa pro plano alvo,
 * atualiza valor_mensal, zera plano_id_pendente.
 *
 * Idempotente: chamar duas vezes não causa problema.
 * Retorna true se aplicou, false se a cobrança não era de upgrade.
 */
class AplicarUpgradePlano
{
    public function executar(Cobranca $cobranca): bool
    {
        $alvoId = $cobranca->meta['upgrade']['plano_alvo_id'] ?? null;
        if (!$alvoId) return false;

        $plano = Plano::find($alvoId);
        if (!$plano) return false;

        $assinatura = $cobranca->assinatura;
        if ($assinatura) {
            $assinatura->update([
                'plano_id'          => $plano->id,
                'plano_id_pendente' => null,
                'valor_mensal'      => $plano->preco_mensal,
                'status'            => 'ativa',
            ]);
        }

        $empresa = Empresa::find($cobranca->empresa_id);
        if ($empresa) {
            $empresa->update(['plano_id' => $plano->id]);
        }

        return true;
    }
}
