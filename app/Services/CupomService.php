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

        return DB::transaction(function () use ($cliente, $beneficio) {
            // lockForUpdate + revalidação DENTRO da transaction. Sem isso,
            // o check `podeResgatarPor` rodava fora da transaction e múltiplas
            // requests paralelas conseguiam burlar `limite_total=1`.
            $beneficio = Beneficio::lockForUpdate()->findOrFail($beneficio->id);

            if (!$beneficio->podeResgatarPor($cliente)) {
                throw new \DomainException('Benefício indisponível ou limite atingido.');
            }

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
     *
     * Race fix: parceiro com 2 abas abertas validava mesmo cupom 2x. Ambas
     * liam status='ativo' e ambas marcavam como usado. Agora lockForUpdate
     * dentro de transaction garante que a 2ª chamada veja status='usado'.
     */
    public function validar(string $secret, string $codigo, ?string $observacao = null): Cupom
    {
        return DB::transaction(function () use ($secret, $codigo, $observacao) {
            $cupom = Cupom::where('codigo', strtoupper($codigo))
                ->whereHas('beneficio.parceiro', fn($q) => $q->where('validacao_secret', $secret))
                ->lockForUpdate()
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
        });
    }
}
