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
            // Lock no Cliente: garante exclusividade durante toda a
            // compra (compra → pontos → cashback → total_gasto/compras).
            // Sem lock no topo, 2 compras paralelas podiam ler total_gasto=100
            // simultâneo e ambas somar 50 → final 150 ao invés de 200.
            // PontuacaoService/CashbackService::creditar fazem seu próprio
            // lockForUpdate, mas se a compra for puro estímulo (pontos=0 E
            // cashback=0) nenhum dos dois rodava — total_gasto ficava aberto.
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);
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

            // Cast int explícito: drivers PDO com emulate_prepares=true podem
            // retornar string "0" e === 0 falharia (failure-closed mas ainda
            // assim quebrava o crédito de indicação legítimo).
            $eraNovo = (int) $cliente->total_compras === 0;

            $cliente->total_gasto = (float) $cliente->total_gasto + $valor;
            $cliente->total_compras = $cliente->total_compras + 1;
            $cliente->ultima_compra = now();
            $cliente->save();

            // Bônus de indicação: credita pro indicador SOMENTE quando o
            // indicado faz a primeira compra. Antes era creditado no cadastro
            // → atacante criava N contas vazias e ganhava pontos do indicador
            // sem nenhuma venda real.
            if ($eraNovo && $cliente->indicado_por_id) {
                try {
                    $regraInd = $empresa->regrasPontuacao()
                        ->where('tipo', 'indicacao')->where('ativo', true)->first();
                    if ($regraInd && $regraInd->vigente() && $regraInd->pontos_fixos > 0) {
                        $indicador = Cliente::find($cliente->indicado_por_id);
                        if ($indicador && $indicador->empresa_id === $empresa->id) {
                            $this->pontuacaoService->creditar(
                                $indicador,
                                $regraInd->pontos_fixos,
                                'indicacao',
                                $cliente,
                                "Indicação convertida: {$cliente->nome} (1ª compra)"
                            );
                        }
                    }
                } catch (Throwable $e) {
                    Log::warning('[Compra] Falha ao creditar bônus de indicação na 1ª compra: '.$e->getMessage(), [
                        'cliente_id' => $cliente->id,
                        'indicador_id' => $cliente->indicado_por_id,
                    ]);
                }
            }

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
