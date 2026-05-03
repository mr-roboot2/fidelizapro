<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesquisa;
use App\Models\RegraPontuacao;
use App\Services\PontuacaoService;
use Illuminate\Http\Request;

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
        $dados = $request->validate([
            'compra_id'  => 'nullable|exists:compras,id',
            'nota'       => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
            'respostas'  => 'nullable|array',
        ]);

        $cliente = $request->user();

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

        $pesquisa = Pesquisa::create([
            'empresa_id' => $cliente->empresa_id,
            'cliente_id' => $cliente->id,
            'compra_id'  => $dados['compra_id'] ?? null,
            'nota'       => $dados['nota'],
            'comentario' => $dados['comentario'] ?? null,
            'respostas'  => $dados['respostas'] ?? null,
        ]);

        // Crédito de pontos só na primeira avaliação (se houver regra ativa)
        $pontosCreditados = 0;
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
