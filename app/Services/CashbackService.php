<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimentoCashback;
use Illuminate\Support\Facades\DB;

class CashbackService
{
    public function calcularCashback(Empresa $empresa, float $valor): float
    {
        if ($empresa->cashback_percentual <= 0) return 0;
        return round($valor * ($empresa->cashback_percentual / 100), 2);
    }

    public function creditar(
        Cliente $cliente,
        float $valor,
        string $origem,
        ?object $referencia = null,
        ?string $descricao = null
    ): MovimentoCashback {
        return DB::transaction(function () use ($cliente, $valor, $origem, $referencia, $descricao) {
            $cliente->refresh();
            $empresa = $cliente->empresa;
            $dias = (int) ($empresa->dias_liberar_cashback ?? 0);
            $pendente = $dias > 0;

            $liberadoEm = now()->addDays($dias);

            $saldoAnterior = (float) $cliente->cashback_atual;
            $saldoPosterior = $pendente ? $saldoAnterior : ($saldoAnterior + $valor);

            if ($pendente) {
                $cliente->cashback_pendente = (float) $cliente->cashback_pendente + $valor;
            } else {
                $cliente->cashback_atual = $saldoPosterior;
            }
            $cliente->save();

            return MovimentoCashback::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'tipo' => 'credito',
                'origem' => $origem,
                'valor' => $valor,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia_type' => $referencia ? get_class($referencia) : null,
                'referencia_id' => $referencia?->id,
                'descricao' => $descricao ?? "Cashback creditado: R$ ".number_format($valor, 2, ',', '.')
                    . ($pendente ? " (libera em {$dias} dias)" : ''),
                'liberado_em' => $liberadoEm,
                'processado' => !$pendente,
            ]);
        });
    }

    public function debitar(
        Cliente $cliente,
        float $valor,
        string $origem,
        ?object $referencia = null,
        ?string $descricao = null
    ): MovimentoCashback {
        return DB::transaction(function () use ($cliente, $valor, $origem, $referencia, $descricao) {
            $cliente->refresh();
            $saldoAnterior = (float) $cliente->cashback_atual;

            if ($saldoAnterior < $valor) {
                throw new \DomainException('Saldo de cashback insuficiente.');
            }

            $saldoPosterior = $saldoAnterior - $valor;
            $cliente->cashback_atual = $saldoPosterior;
            $cliente->save();

            return MovimentoCashback::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'tipo' => 'debito',
                'origem' => $origem,
                'valor' => $valor,
                'saldo_anterior' => $saldoAnterior,
                'saldo_posterior' => $saldoPosterior,
                'referencia_type' => $referencia ? get_class($referencia) : null,
                'referencia_id' => $referencia?->id,
                'descricao' => $descricao ?? "Cashback utilizado: R$ ".number_format($valor, 2, ',', '.'),
                'liberado_em' => now(),
                'processado' => true,
            ]);
        });
    }

    /**
     * Libera cashbacks pendentes que passaram do prazo.
     * Chamado pelo comando agendado `cashback:liberar`.
     */
    public function liberarPendentes(): int
    {
        $movimentos = MovimentoCashback::where('tipo', 'credito')
            ->where('processado', false)
            ->whereNotNull('liberado_em')
            ->where('liberado_em', '<=', now())
            ->get();

        $contador = 0;
        foreach ($movimentos as $mov) {
            DB::transaction(function () use ($mov, &$contador) {
                $cliente = $mov->cliente;
                $cliente->refresh();
                $valor = (float) $mov->valor;

                $cliente->cashback_pendente = max(0, (float) $cliente->cashback_pendente - $valor);
                $cliente->cashback_atual = (float) $cliente->cashback_atual + $valor;
                $cliente->save();

                $mov->update(['processado' => true, 'saldo_posterior' => $cliente->cashback_atual]);
                $contador++;
            });
        }
        return $contador;
    }
}
