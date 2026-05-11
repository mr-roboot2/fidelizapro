<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Indicacao;
use App\Models\Roleta;
use App\Models\RoletaGatilho;
use App\Models\RoletaGatilhoDisparo;

class ProcessarGatilhosRoletaService
{
    public function __construct(private RoletaService $roleta) {}

    /**
     * Processa todos gatilhos ativos da roleta e retorna resumo por tipo:
     * ['aniversario' => 3, 'compra_acima' => 1, ...].
     */
    public function processar(Roleta $roleta): array
    {
        $resumo = [];
        foreach ($roleta->gatilhos()->where('ativo', true)->get() as $g) {
            $resumo[$g->tipo] = match ($g->tipo) {
                'aniversario'         => $this->aniversario($roleta, $g),
                'indicacao'           => $this->indicacao($roleta, $g),
                'compra_acima'        => $this->compraAcima($roleta, $g),
                'inativo_dias'        => $this->inativoDias($roleta, $g),
                'atingiu_pontos'      => $this->atingiuPontos($roleta, $g),
                'vip_gasto'           => $this->vipGasto($roleta, $g),
                'recorrente_compras'  => $this->recorrenteCompras($roleta, $g),
                'dia_fraco'           => $this->diaFraco($roleta, $g),
                default               => 0,
            };
        }
        return $resumo;
    }

    /**
     * Crédito + log de disparo idempotente. Retorna true se foi a primeira vez.
     */
    public function disparar(Roleta $roleta, Cliente $cliente, string $tipo, string $referencia, int $giros): bool
    {
        $disparo = RoletaGatilhoDisparo::firstOrCreate(
            [
                'roleta_id'  => $roleta->id,
                'cliente_id' => $cliente->id,
                'referencia' => $referencia,
            ],
            [
                'tipo'             => $tipo,
                'giros_creditados' => $giros,
            ]
        );

        if (!$disparo->wasRecentlyCreated) {
            return false;
        }

        $this->roleta->creditar($roleta, $cliente, $giros, 'manual', null, $this->motivoTexto($tipo));
        return true;
    }

    private function motivoTexto(string $tipo): string
    {
        return match ($tipo) {
            'primeiro_cadastro'   => 'boas-vindas ao programa',
            'aniversario'         => 'hoje é seu aniversário 🎂',
            'indicacao'           => 'sua indicação se cadastrou',
            'compra_acima'        => 'sua compra de hoje',
            'inativo_dias'        => 'estávamos com saudade',
            'atingiu_pontos'      => 'você atingiu uma meta de pontos',
            'vip_gasto'           => 'você é cliente VIP da loja 👑',
            'recorrente_compras'  => 'cliente fiel, obrigado por sempre voltar 💛',
            'dia_fraco'           => 'hoje é dia da Roleta da Sorte, dá uma passada na loja 😉',
            default               => 'cortesia da loja',
        };
    }

    private function aniversario(Roleta $roleta, RoletaGatilho $g): int
    {
        $hoje = now();
        $clientes = Cliente::where('empresa_id', $roleta->empresa_id)
            ->where('ativo', true)
            ->whereNotNull('data_nascimento')
            ->whereRaw('DATE_FORMAT(data_nascimento, "%m-%d") = ?', [$hoje->format('m-d')])
            ->get();

        $ref = 'aniversario:'.$hoje->format('Y-m-d');
        $n = 0;
        foreach ($clientes as $c) {
            if ($this->disparar($roleta, $c, 'aniversario', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    private function indicacao(Roleta $roleta, RoletaGatilho $g): int
    {
        $indicacoes = Indicacao::where('empresa_id', $roleta->empresa_id)
            ->whereIn('status', ['cadastrado', 'convertido'])
            ->whereNotNull('cliente_indicador_id')
            ->get();

        $n = 0;
        foreach ($indicacoes as $ind) {
            $cliente = Cliente::find($ind->cliente_indicador_id);
            if (!$cliente) continue;
            $ref = 'indicacao:'.$ind->id;
            if ($this->disparar($roleta, $cliente, 'indicacao', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    private function compraAcima(Roleta $roleta, RoletaGatilho $g): int
    {
        $valorMin = (float) ($g->valor ?? 0);
        if ($valorMin <= 0) return 0;

        $compras = Compra::where('empresa_id', $roleta->empresa_id)
            ->where('valor', '>=', $valorMin)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $n = 0;
        foreach ($compras as $compra) {
            $cliente = Cliente::find($compra->cliente_id);
            if (!$cliente) continue;
            $ref = 'compra_acima:'.((int) $valorMin).':'.$compra->id;
            if ($this->disparar($roleta, $cliente, 'compra_acima', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    private function inativoDias(Roleta $roleta, RoletaGatilho $g): int
    {
        $dias = (int) ($g->valor ?? 0);
        if ($dias <= 0) return 0;

        $clientes = Cliente::where('empresa_id', $roleta->empresa_id)
            ->where('ativo', true)
            ->whereNotNull('ultima_compra')
            ->whereDate('ultima_compra', now()->subDays($dias)->toDateString())
            ->get();

        $n = 0;
        foreach ($clientes as $c) {
            $ref = 'inativo_dias:'.$dias.':'.optional($c->ultima_compra)->format('Y-m-d');
            if ($this->disparar($roleta, $c, 'inativo_dias', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    private function atingiuPontos(Roleta $roleta, RoletaGatilho $g): int
    {
        $minimo = (int) ($g->valor ?? 0);
        if ($minimo <= 0) return 0;

        $clientes = Cliente::where('empresa_id', $roleta->empresa_id)
            ->where('ativo', true)
            ->where('pontos_atual', '>=', $minimo)
            ->get();

        $n = 0;
        foreach ($clientes as $c) {
            $ref = 'atingiu_pontos:'.$minimo;
            if ($this->disparar($roleta, $c, 'atingiu_pontos', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    private function vipGasto(Roleta $roleta, RoletaGatilho $g): int
    {
        $minimo = (float) ($g->valor ?? 0);
        if ($minimo <= 0) return 0;

        $clientes = Cliente::where('empresa_id', $roleta->empresa_id)
            ->where('ativo', true)
            ->where('total_gasto', '>=', $minimo)
            ->get();

        $n = 0;
        foreach ($clientes as $c) {
            $ref = 'vip_gasto:'.((int) $minimo);
            if ($this->disparar($roleta, $c, 'vip_gasto', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    private function recorrenteCompras(Roleta $roleta, RoletaGatilho $g): int
    {
        $minimo = (int) ($g->valor ?? 0);
        if ($minimo <= 0) return 0;

        $clientes = Cliente::where('empresa_id', $roleta->empresa_id)
            ->where('ativo', true)
            ->where('total_compras', '>=', $minimo)
            ->get();

        $n = 0;
        foreach ($clientes as $c) {
            $ref = 'recorrente_compras:'.$minimo;
            if ($this->disparar($roleta, $c, 'recorrente_compras', $ref, $g->giros)) $n++;
        }
        return $n;
    }

    /**
     * Analisa as últimas 4 semanas de compras agrupadas por dia da semana
     * e dispara o gatilho se HOJE estiver entre os N dias menos movimentados
     * (N = $g->valor, default 2). Idempotente por data — todo cliente ativo
     * recebe 1 giro no dia fraco.
     *
     * Silenciosamente pula se a empresa tem menos de 4 dias da semana com
     * histórico — ranking não confiável.
     */
    private function diaFraco(Roleta $roleta, RoletaGatilho $g): int
    {
        $bottomN = max(1, min(3, (int) ($g->valor ?? 2)));
        $hoje = now();
        $diaSemanaHoje = (int) $hoje->dayOfWeek; // 0=Dom .. 6=Sáb (Carbon)

        $contagem = Compra::where('empresa_id', $roleta->empresa_id)
            ->where('created_at', '>=', $hoje->copy()->subWeeks(4)->startOfDay())
            ->selectRaw('DAYOFWEEK(created_at) - 1 as dia_semana, COUNT(*) as total')
            ->groupBy('dia_semana')
            ->orderBy('total')
            ->pluck('total', 'dia_semana');

        if ($contagem->count() < 4) return 0;

        $diasFracos = $contagem->take($bottomN)->keys()->map(fn ($d) => (int) $d);
        if (!$diasFracos->contains($diaSemanaHoje)) return 0;

        $clientes = Cliente::where('empresa_id', $roleta->empresa_id)
            ->where('ativo', true)
            ->get();

        $ref = 'dia_fraco:'.$hoje->toDateString();
        $n = 0;
        foreach ($clientes as $c) {
            if ($this->disparar($roleta, $c, 'dia_fraco', $ref, $g->giros)) $n++;
        }
        return $n;
    }
}
