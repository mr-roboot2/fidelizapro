<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimentoCashback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CashbackService
{
    public function __construct(protected ?WhatsappService $whatsapp = null) {}

    public function calcularCashback(Empresa $empresa, float $valor): float
    {
        if (!$empresa->usaCashback()) return 0;
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

            $valor = round((float) $valor, 2);
            $saldoAnterior = round((float) $cliente->cashback_atual, 2);
            $saldoPosterior = $pendente ? $saldoAnterior : round($saldoAnterior + $valor, 2);

            if ($pendente) {
                $cliente->cashback_pendente = round((float) $cliente->cashback_pendente + $valor, 2);
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
            $saldoAnterior = round((float) $cliente->cashback_atual, 2);
            $valor = round((float) $valor, 2);

            if (round($saldoAnterior - $valor, 2) < 0) {
                throw new \DomainException('Saldo de cashback insuficiente.');
            }

            $saldoPosterior = round($saldoAnterior - $valor, 2);
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
        $clientesNotificar = [];

        foreach ($movimentos as $mov) {
            DB::transaction(function () use ($mov, &$contador, &$clientesNotificar) {
                $cliente = $mov->cliente;
                $cliente->refresh();
                $valor = round((float) $mov->valor, 2);

                $cliente->cashback_pendente = round(max(0, (float) $cliente->cashback_pendente - $valor), 2);
                $cliente->cashback_atual = round((float) $cliente->cashback_atual + $valor, 2);
                $cliente->save();

                $mov->update(['processado' => true, 'saldo_posterior' => $cliente->cashback_atual]);
                $contador++;

                $clientesNotificar[$cliente->id] = $cliente;
            });
        }

        // Notifica via WhatsApp (após o commit das transactions)
        if ($this->whatsapp) {
            foreach ($clientesNotificar as $cliente) {
                try {
                    $valorFmt = number_format((float) $cliente->cashback_atual, 2, ',', '.');
                    $this->whatsapp->enviarEvento(
                        $cliente->empresa,
                        $cliente->telefone,
                        'cashback_disponivel',
                        [$cliente->nome, $valorFmt],
                        "Olá {$cliente->nome}! Seu cashback de R\$ {$valorFmt} foi liberado e já está disponível no app."
                    );
                } catch (Throwable $e) {
                    Log::warning("[Cashback] Falha ao notificar cliente {$cliente->id}: ".$e->getMessage());
                }
            }
        }

        return $contador;
    }
}
