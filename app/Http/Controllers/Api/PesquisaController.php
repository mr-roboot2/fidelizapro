<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesquisa;
use App\Models\RegraPontuacao;
use App\Models\TransacaoPonto;
use App\Services\PontuacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesquisaController extends Controller
{
    /**
     * Retorna a avaliação geral atual do cliente (sem compra_id), se existir.
     * Permite ao PWA decidir entre exibir form de criação ou de edição.
     */
    public function minhaGeral(Request $request)
    {
        $cliente = $request->user();
        $pesquisa = Pesquisa::where('cliente_id', $cliente->id)
            ->whereNull('compra_id')
            ->latest()
            ->first();

        return response()->json(['pesquisa' => $pesquisa]);
    }

    public function responder(Request $request, PontuacaoService $pontuacaoService)
    {
        try {
            return $this->responderInterno($request, $pontuacaoService);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    protected function responderInterno(Request $request, PontuacaoService $pontuacaoService)
    {
        $cliente = $request->user();

        // Escopa exists: à empresa do cliente. Sem isso, o cliente podia
        // passar compra_id de QUALQUER empresa (cross-tenant) e poluir o
        // histórico de avaliações com referência a compras alheias.
        $dados = $request->validate([
            'compra_id'  => [
                'nullable',
                \Illuminate\Validation\Rule::exists('compras', 'id')
                    ->where('empresa_id', $cliente->empresa_id)
                    ->where('cliente_id', $cliente->id),
            ],
            'nota'       => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
            'respostas'  => 'nullable|array',
        ]);

        // 1 avaliação por compra (quando compra_id é enviado)
        if (!empty($dados['compra_id'])) {
            $jaRespondida = Pesquisa::where('cliente_id', $cliente->id)
                ->where('compra_id', $dados['compra_id'])->exists();
            if ($jaRespondida) {
                return response()->json(['message' => 'Você já avaliou esta compra.'], 422);
            }
        } else {
            // 1 avaliação geral por cliente — se já existir, redireciona pra edição
            $existente = Pesquisa::where('cliente_id', $cliente->id)
                ->whereNull('compra_id')->first();
            if ($existente) {
                return response()->json([
                    'message' => 'Você já tem uma avaliação geral. Use Editar para alterar.',
                    'pesquisa_existente_id' => $existente->id,
                ], 422);
            }
        }

        // Wrap em transaction + lock no Cliente ANTES do create:
        // 2 requests paralelas com mesmo compra_id liam jaRespondida=false
        // pré-transaction, ambas entravam aqui e criavam pesquisas duplicadas
        // (dados sujos). Agora o lock ordena e o recheck dentro do lock
        // detecta o duplicate. O `Pesquisa::create` só roda depois do recheck.
        [$pesquisa, $pontosCreditados] = DB::transaction(function () use ($cliente, $dados, $pontuacaoService) {
            // Lock no Cliente serializa o fluxo inteiro de avaliação por cliente.
            \App\Models\Cliente::lockForUpdate()->findOrFail($cliente->id);

            // Recheck dentro do lock: se outra request paralela já criou
            // pesquisa pra esse compra_id (ou geral), aborta.
            if (!empty($dados['compra_id'])) {
                if (Pesquisa::where('cliente_id', $cliente->id)
                    ->where('compra_id', $dados['compra_id'])->exists()) {
                    throw new \DomainException('Você já avaliou esta compra.');
                }
            } else {
                if (Pesquisa::where('cliente_id', $cliente->id)
                    ->whereNull('compra_id')->exists()) {
                    throw new \DomainException('Você já tem uma avaliação geral. Use Editar para alterar.');
                }
            }

            $pesquisa = Pesquisa::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'compra_id'  => $dados['compra_id'] ?? null,
                'nota'       => $dados['nota'],
                'comentario' => $dados['comentario'] ?? null,
                'respostas'  => $dados['respostas'] ?? null,
            ]);

            $pontosCreditados = 0;
            $jaCreditouAvaliacao = TransacaoPonto::where('cliente_id', $cliente->id)
                ->where('referencia_type', Pesquisa::class)
                ->where('tipo', 'credito')
                ->exists();

            if (!$jaCreditouAvaliacao) {
                $regra = RegraPontuacao::where('empresa_id', $cliente->empresa_id)
                    ->where('tipo', 'avaliacao')
                    ->where('ativo', true)
                    ->first();

                if ($regra && $regra->vigente() && $regra->pontos_fixos > 0) {
                    $pontosCreditados = $regra->pontos_fixos;
                    $pontuacaoService->creditar(
                        $cliente,
                        $pontosCreditados,
                        'manual',
                        $pesquisa,
                        "Pontos por avaliação (nota {$dados['nota']})"
                    );
                }
            }

            return [$pesquisa, $pontosCreditados];
        });

        return response()->json([
            'message' => $pontosCreditados > 0
                ? "Obrigado! Você ganhou {$pontosCreditados} pontos pela avaliação."
                : 'Obrigado pela avaliação!',
            'pesquisa' => $pesquisa,
            'pontos_creditados' => $pontosCreditados,
            'novo_saldo_pontos' => (float) $cliente->fresh()->pontos_atual,
        ], 201);
    }

    public function atualizar(Request $request, int $id)
    {
        $cliente = $request->user();
        $pesquisa = Pesquisa::where('cliente_id', $cliente->id)->findOrFail($id);

        $dados = $request->validate([
            'nota'       => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
        ]);

        $pesquisa->update($dados);

        return response()->json([
            'message'  => 'Avaliação atualizada!',
            'pesquisa' => $pesquisa->fresh(),
        ]);
    }

    public function excluir(Request $request, int $id)
    {
        $cliente = $request->user();
        $pesquisa = Pesquisa::where('cliente_id', $cliente->id)->findOrFail($id);
        $pesquisa->delete();

        return response()->json(['message' => 'Avaliação removida.']);
    }
}
