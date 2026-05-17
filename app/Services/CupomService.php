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
        // Parceiro inativo: Beneficio::disponivel() só checava
        // $beneficio->ativo, não o parceiro. Cupom era emitido até quando
        // o parceiro tava desligado — operador chegava lá, não tinha como
        // honrar. Bloqueia aqui ANTES de criar (controller já filtra
        // listagem por parceiro ativo, mas é defesa em profundidade).
        if (!$beneficio->parceiro->ativo) {
            throw new \DomainException('Parceiro não está ativo.');
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
        // Lookup do cupom FORA da transaction. Antes o `update(status=expirado)`
        // ficava dentro do DB::transaction e o throw subsequente rolava
        // back o update — cupom continuava `disponivel` no banco, mesma
        // request seguinte tentava de novo, infinito. Agora a transação
        // só envolve o caminho de sucesso (lock + marca usado).
        $cupom = Cupom::where('codigo', strtoupper($codigo))
            ->whereHas('beneficio.parceiro', fn($q) => $q->where('validacao_secret', $secret))
            ->firstOrFail();

        if ($cupom->status === 'usado') {
            throw new \DomainException("Cupom já foi usado em ".$cupom->usado_em->format('d/m/Y H:i'));
        }
        if ($cupom->expirado()) {
            // Marca expirado fora de transaction — persiste mesmo com throw.
            $cupom->update(['status' => 'expirado']);
            throw new \DomainException('Cupom expirado.');
        }

        return DB::transaction(function () use ($cupom, $observacao) {
            $lockado = Cupom::lockForUpdate()->find($cupom->id);
            // Recheck status pós-lock (parceiro com 2 abas abertas validando ao mesmo tempo)
            if ($lockado->status === 'usado') {
                throw new \DomainException("Cupom já foi usado em ".$lockado->usado_em->format('d/m/Y H:i'));
            }

            $lockado->update([
                'status'         => 'usado',
                'usado_em'       => now(),
                'observacao_uso' => $observacao,
            ]);

            return $lockado;
        });
    }
}
