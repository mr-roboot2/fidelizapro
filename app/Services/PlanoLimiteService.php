<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empresa;
use App\Models\Parceiro;
use App\Models\Recompensa;
use App\Models\User;

class PlanoLimiteService
{
    /**
     * Retorna o consumo atual da empresa em cada limite.
     */
    public function consumo(Empresa $empresa): array
    {
        $plano = $empresa->plano;

        $clientesAtual = Cliente::where('empresa_id', $empresa->id)->count();
        $comprasMes = Compra::where('empresa_id', $empresa->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $recompensasAtivas = Recompensa::where('empresa_id', $empresa->id)->where('ativo', true)->count();
        $parceirosAtivos = Parceiro::where('empresa_id', $empresa->id)->where('ativo', true)->count();
        $usersAtivos = User::where('empresa_id', $empresa->id)->count();
        $campanhasMes = Campanha::where('empresa_id', $empresa->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        return [
            'clientes' => [
                'atual' => $clientesAtual,
                'limite' => $plano?->limite_clientes,
                'percentual' => $this->pct($clientesAtual, $plano?->limite_clientes),
            ],
            'compras_mes' => [
                'atual' => $comprasMes,
                'limite' => $plano?->limite_compras_mes,
                'percentual' => $this->pct($comprasMes, $plano?->limite_compras_mes),
            ],
            'recompensas' => [
                'atual' => $recompensasAtivas,
                'limite' => $plano?->limite_recompensas,
                'percentual' => $this->pct($recompensasAtivas, $plano?->limite_recompensas),
            ],
            'parceiros' => [
                'atual' => $parceirosAtivos,
                'limite' => $plano?->limite_parceiros,
                'percentual' => $this->pct($parceirosAtivos, $plano?->limite_parceiros),
            ],
            'users' => [
                'atual' => $usersAtivos,
                'limite' => $plano?->limite_users,
                'percentual' => $this->pct($usersAtivos, $plano?->limite_users),
            ],
            'campanhas_mes' => [
                'atual' => $campanhasMes,
                'limite' => $plano?->limite_campanhas_mes,
                'percentual' => $this->pct($campanhasMes, $plano?->limite_campanhas_mes),
            ],
        ];
    }

    /**
     * Lança DomainException se o limite estiver atingido.
     */
    public function garantirCapacidade(Empresa $empresa, string $recurso): void
    {
        $consumo = $this->consumo($empresa);
        if (!isset($consumo[$recurso])) return;

        $atual = $consumo[$recurso]['atual'];
        $limite = $consumo[$recurso]['limite'];
        if ($limite !== null && $atual >= $limite) {
            throw new \DomainException("Limite do plano atingido para {$recurso}: {$atual}/{$limite}. Faça upgrade do plano para continuar.");
        }
    }

    public function recursoDisponivel(Empresa $empresa, string $flag): bool
    {
        $plano = $empresa->plano;
        if (!$plano) return true; // sem plano = livre (modo dev)
        return (bool) ($plano->{$flag} ?? false);
    }

    protected function pct(int $atual, ?int $limite): ?float
    {
        if ($limite === null || $limite === 0) return null;
        return round(($atual / $limite) * 100, 1);
    }
}
