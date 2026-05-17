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
        // Conta SÓ gerentes + atendentes — o admin "dono" da empresa não
        // entra no limite (já pagou o plano, não é parte da equipe operacional).
        // super_admin nunca tem empresa_id, então fica de fora naturalmente.
        $usersAtivos = User::where('empresa_id', $empresa->id)
            ->whereIn('role', ['gerente', 'atendente'])->count();
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

    /**
     * Verifica compatibilidade da empresa com o plano alvo. Retorna:
     *   - bloqueadores: consumo atual EXCEDE o limite do plano alvo. Esses
     *     itens devem impedir o downgrade (cliente precisa reduzir antes).
     *     Ex: empresa tem 150 clientes mas alvo aceita só 100.
     *   - informativos: o que vai mudar mas não bloqueia (módulos que
     *     ela perde, mas cliente pode aceitar perder).
     *
     * Recursos "mês" (compras_mes, campanhas_mes) NÃO bloqueiam: como o
     * contador zera no mês seguinte, segurar downgrade por isso seria
     * injusto (cliente esperaria o mês virar).
     */
    public function avisosCompatibilidade(Empresa $empresa, \App\Models\Plano $planoAlvo): array
    {
        $consumo = $this->consumo($empresa);

        // Recursos persistentes que EXCEDIDOS bloqueiam o downgrade
        $bloqueantes = [
            'clientes'    => ['campo' => 'limite_clientes',    'label' => 'clientes'],
            'recompensas' => ['campo' => 'limite_recompensas', 'label' => 'recompensas ativas'],
            'parceiros'   => ['campo' => 'limite_parceiros',   'label' => 'parceiros ativos'],
            'users'       => ['campo' => 'limite_users',       'label' => 'atendentes'],
        ];

        // Recursos "do mês" — só avisa, não bloqueia
        $informativos = [
            'compras_mes'   => ['campo' => 'limite_compras_mes',   'label' => 'compras este mês'],
            'campanhas_mes' => ['campo' => 'limite_campanhas_mes', 'label' => 'campanhas este mês'],
        ];

        $resultado = ['bloqueadores' => [], 'informativos' => []];

        foreach ($bloqueantes as $chave => $info) {
            $limiteAlvo = $planoAlvo->{$info['campo']} ?? null;
            $atual = $consumo[$chave]['atual'] ?? 0;
            if ($limiteAlvo !== null && $atual > $limiteAlvo) {
                $resultado['bloqueadores'][] = "{$atual} {$info['label']} (limite do plano: {$limiteAlvo}). Reduza antes de mudar.";
            }
        }

        foreach ($informativos as $chave => $info) {
            $limiteAlvo = $planoAlvo->{$info['campo']} ?? null;
            $atual = $consumo[$chave]['atual'] ?? 0;
            if ($limiteAlvo !== null && $atual > $limiteAlvo) {
                $resultado['informativos'][] = "{$atual} {$info['label']} (limite do plano: {$limiteAlvo}). Próximo mês zera.";
            }
        }

        // Módulos perdidos: informativo (cliente aceita perder feature)
        $modulosAtuais = $empresa->plano?->modulos ?? [];
        $modulosAlvo = $planoAlvo->modulos ?? [];
        foreach (array_diff($modulosAtuais, $modulosAlvo) as $mod) {
            $rotulo = \App\Models\Plano::rotulosModulos()[$mod] ?? $mod;
            $resultado['informativos'][] = "Perderá acesso: {$rotulo}";
        }

        return $resultado;
    }

    protected function pct(int $atual, ?int $limite): ?float
    {
        if ($limite === null || $limite === 0) return null;
        return round(($atual / $limite) * 100, 1);
    }
}
