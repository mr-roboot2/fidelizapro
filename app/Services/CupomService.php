<?php

namespace App\Services;

use App\Models\Beneficio;
use App\Models\Cliente;
use App\Models\Cupom;
use Illuminate\Support\Facades\DB;

class CupomService
{
    /**
     * Cliente solicita ativação de um cupom.
     */
    public function gerar(Cliente $cliente, Beneficio $beneficio): Cupom
    {
        if ($cliente->empresa_id !== $beneficio->parceiro->empresa_id) {
            throw new \DomainException('Benefício não pertence à empresa do cliente.');
        }

        if (!$beneficio->podeResgatarPor($cliente)) {
            throw new \DomainException('Benefício indisponível ou limite atingido.');
        }

        return DB::transaction(function () use ($cliente, $beneficio) {
            $cupom = Cupom::create([
                'beneficio_id' => $beneficio->id,
                'cliente_id' => $cliente->id,
                'valido_ate' => $beneficio->valido_ate?->endOfDay() ?? now()->addDays(30),
            ]);

            $beneficio->increment('total_resgatados');

            return $cupom;
        });
    }

    /**
     * Parceiro valida o cupom (na tela pública).
     */
    public function validar(string $secret, string $codigo, ?string $observacao = null): Cupom
    {
        $cupom = Cupom::where('codigo', strtoupper($codigo))
            ->whereHas('beneficio.parceiro', fn($q) => $q->where('validacao_secret', $secret))
            ->firstOrFail();

        if ($cupom->status === 'usado') {
            throw new \DomainException("Cupom já foi usado em ".$cupom->usado_em->format('d/m/Y H:i'));
        }
        if ($cupom->expirado()) {
            $cupom->update(['status' => 'expirado']);
            throw new \DomainException('Cupom expirado.');
        }

        $cupom->update([
            'status' => 'usado',
            'usado_em' => now(),
            'observacao_uso' => $observacao,
        ]);

        return $cupom;
    }
}
