<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Recompensa;
use App\Models\Resgate;
use App\Models\User;
use App\Services\AutomacaoService;
use Illuminate\Support\Facades\DB;

class ResgateService
{
    public function __construct(
        protected PontuacaoService $pontuacaoService,
        protected AutomacaoService $automacaoService
    ) {}

    public function solicitar(Cliente $cliente, Recompensa $recompensa, ?string $observacao = null, ?string $ip = null): Resgate
    {
        return DB::transaction(function () use ($cliente, $recompensa, $observacao, $ip) {
            if ($cliente->empresa_id !== $recompensa->empresa_id) {
                throw new \DomainException('Recompensa não pertence à empresa do cliente.');
            }

            if (!$recompensa->disponivel()) {
                throw new \DomainException('Recompensa indisponível.');
            }

            if ($cliente->pontos_atual < $recompensa->custo_pontos) {
                throw new \DomainException('Pontos insuficientes para resgatar.');
            }

            // Antifraude: max 3 resgates por cliente em 24h
            $resgatesUltimas24h = Resgate::where('cliente_id', $cliente->id)
                ->where('created_at', '>=', now()->subDay())
                ->where('status', '!=', 'cancelado')
                ->count();
            if ($resgatesUltimas24h >= 3) {
                throw new \DomainException('Limite de 3 resgates em 24h atingido. Tente novamente amanhã.');
            }

            $resgate = Resgate::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'recompensa_id' => $recompensa->id,
                'pontos_usados' => $recompensa->custo_pontos,
                'status' => 'pendente',
                'observacao' => $observacao,
                'ip' => $ip,
            ]);

            $this->pontuacaoService->debitar(
                $cliente,
                $recompensa->custo_pontos,
                'resgate',
                $resgate,
                "Resgate de '{$recompensa->nome}'"
            );

            if ($recompensa->estoque !== null) {
                $recompensa->decrement('estoque');
            }

            return $resgate;
        });
    }

    public function aprovar(Resgate $resgate, User $aprovador): Resgate
    {
        if ($resgate->status !== 'pendente') {
            throw new \DomainException('Resgate não está pendente.');
        }

        $resgate->update([
            'status' => 'aprovado',
            'aprovado_por' => $aprovador->id,
            'aprovado_em' => now(),
        ]);

        $this->automacaoService->disparar($resgate->empresa, 'agradecimento_resgate', $resgate->cliente, [
            '{recompensa}' => $resgate->recompensa->nome,
            '{codigo_resgate}' => $resgate->codigo,
        ]);

        return $resgate->fresh();
    }

    public function entregar(Resgate $resgate): Resgate
    {
        if (!in_array($resgate->status, ['aprovado', 'pendente'])) {
            throw new \DomainException('Resgate não pode ser entregue.');
        }

        $resgate->update([
            'status' => 'entregue',
            'entregue_em' => now(),
        ]);

        return $resgate->fresh();
    }

    public function cancelar(Resgate $resgate, ?string $motivo = null): Resgate
    {
        if ($resgate->status === 'entregue') {
            throw new \DomainException('Resgate já entregue não pode ser cancelado.');
        }

        DB::transaction(function () use ($resgate, $motivo) {
            $this->pontuacaoService->creditar(
                $resgate->cliente,
                $resgate->pontos_usados,
                'manual',
                $resgate,
                "Estorno do resgate #{$resgate->codigo}"
            );

            if ($resgate->recompensa->estoque !== null) {
                $resgate->recompensa->increment('estoque');
            }

            $resgate->update([
                'status' => 'cancelado',
                'cancelado_em' => now(),
                'observacao' => trim(($resgate->observacao ?? '')."\nCancelado: ".$motivo),
            ]);
        });

        return $resgate->fresh();
    }
}
