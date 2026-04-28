<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empresa;
use App\Models\RegraPontuacao;
use App\Models\TransacaoPonto;
use Illuminate\Support\Facades\DB;

class PontuacaoService
{
    public function calcularPontosCompra(Empresa $empresa, float $valor): float
    {
        $regra = RegraPontuacao::where('empresa_id', $empresa->id)
            ->where('tipo', 'compra')
            ->where('ativo', true)
            ->where('valor_minimo', '<=', $valor)
            ->where(function ($q) use ($valor) {
                $q->whereNull('valor_maximo')->orWhere('valor_maximo', '>=', $valor);
            })
            ->orderByDesc('multiplicador')
            ->first();

        if ($regra && $regra->vigente()) {
            return round($valor * $regra->pontos_por_real * $regra->multiplicador, 2);
        }

        return round($valor * $empresa->pontos_por_real, 2);
    }

    public function creditar(
        Cliente $cliente,
        float $pontos,
        string $origem,
        ?object $referencia = null,
        ?string $descricao = null
    ): TransacaoPonto {
        return DB::transaction(function () use ($cliente, $pontos, $origem, $referencia, $descricao) {
            $cliente->refresh();
            $saldoAnterior = (float) $cliente->pontos_atual;
            $saldoPosterior = $saldoAnterior + $pontos;

            $cliente->pontos_atual = $saldoPosterior;
            $cliente->save();

            return TransacaoPonto::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'tipo' => 'credito',
                'origem' => $origem,
                'pontos' => $pontos,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia_type' => $referencia ? get_class($referencia) : null,
                'referencia_id' => $referencia?->id,
                'descricao' => $descricao ?? "Crédito de {$pontos} pontos ({$origem})",
                'expira_em' => now()->addDays($cliente->empresa->validade_pontos_dias),
            ]);
        });
    }

    public function debitar(
        Cliente $cliente,
        float $pontos,
        string $origem,
        ?object $referencia = null,
        ?string $descricao = null
    ): TransacaoPonto {
        return DB::transaction(function () use ($cliente, $pontos, $origem, $referencia, $descricao) {
            $cliente->refresh();
            $saldoAnterior = (float) $cliente->pontos_atual;

            if ($saldoAnterior < $pontos) {
                throw new \DomainException('Saldo de pontos insuficiente.');
            }

            $saldoPosterior = $saldoAnterior - $pontos;
            $cliente->pontos_atual = $saldoPosterior;
            $cliente->save();

            return TransacaoPonto::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'tipo' => 'debito',
                'origem' => $origem,
                'pontos' => $pontos,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia_type' => $referencia ? get_class($referencia) : null,
                'referencia_id' => $referencia?->id,
                'descricao' => $descricao ?? "Débito de {$pontos} pontos ({$origem})",
            ]);
        });
    }
}
