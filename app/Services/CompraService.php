<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Compra;
use App\Services\AutomacaoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CompraService
{
    public function __construct(
        protected PontuacaoService $pontuacaoService,
        protected CashbackService $cashbackService,
        protected AutomacaoService $automacaoService
    ) {}

    public function registrar(Cliente $cliente, array $dados): Compra
    {
        return DB::transaction(function () use ($cliente, $dados) {
            $valor = (float) $dados['valor'];
            $empresa = $cliente->empresa;

            $pontos = $this->pontuacaoService->calcularPontosCompra($empresa, $valor);
            $cashback = $this->cashbackService->calcularCashback($empresa, $valor);

            $compra = Compra::create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $cliente->id,
                'user_id' => $dados['user_id'] ?? null,
                'codigo' => $dados['codigo'] ?? null,
                'valor' => $valor,
                'desconto' => $dados['desconto'] ?? 0,
                'pontos_gerados' => $pontos,
                'cashback_gerado' => $cashback,
                'descricao' => $dados['descricao'] ?? null,
                'origem' => $dados['origem'] ?? 'manual',
                'ip' => $dados['ip'] ?? null,
                'meta' => $dados['meta'] ?? null,
            ]);

            if ($pontos > 0) {
                $this->pontuacaoService->creditar($cliente, $pontos, 'compra', $compra,
                    "Pontos pela compra #{$compra->id} (R$ ".number_format($valor, 2, ',', '.').")");
            }

            if ($cashback > 0) {
                $this->cashbackService->creditar($cliente, $cashback, 'compra', $compra,
                    "Cashback pela compra #{$compra->id}");
            }

            $cliente->total_gasto = (float) $cliente->total_gasto + $valor;
            $cliente->total_compras = $cliente->total_compras + 1;
            $cliente->ultima_compra = now();
            $cliente->save();

            // Dispara automação pós-compra (se configurada). Side-effect: nunca
            // pode derrubar o registro da compra se o WhatsApp falhar.
            try {
                $this->automacaoService->disparar($empresa, 'pos_compra', $cliente->fresh(), [
                    '{valor_compra}' => 'R$ '.number_format($valor, 2, ',', '.'),
                    '{pontos_ganhos}' => number_format($pontos, 0, ',', '.'),
                ]);
            } catch (Throwable $e) {
                Log::warning('[Compra] Falha ao disparar automação pos_compra: '.$e->getMessage(), [
                    'compra_id' => $compra->id,
                    'cliente_id' => $cliente->id,
                ]);
            }

            return $compra;
        });
    }
}
