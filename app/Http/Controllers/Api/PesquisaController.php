<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesquisa;
use App\Models\RegraPontuacao;
use App\Services\PontuacaoService;
use Illuminate\Http\Request;

class PesquisaController extends Controller
{
    public function responder(Request $request, PontuacaoService $pontuacaoService)
    {
        $dados = $request->validate([
            'compra_id' => 'nullable|exists:compras,id',
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
            'respostas' => 'nullable|array',
        ]);

        $cliente = $request->user();

        // Evita duplicidade: 1 pesquisa por compra
        if (!empty($dados['compra_id'])) {
            $jaRespondida = Pesquisa::where('cliente_id', $cliente->id)
                ->where('compra_id', $dados['compra_id'])->exists();
            if ($jaRespondida) {
                return response()->json(['message' => 'Você já avaliou esta compra.'], 422);
            }
        }

        $pesquisa = Pesquisa::create([
            'empresa_id' => $cliente->empresa_id,
            'cliente_id' => $cliente->id,
            'compra_id' => $dados['compra_id'] ?? null,
            'nota' => $dados['nota'],
            'comentario' => $dados['comentario'] ?? null,
            'respostas' => $dados['respostas'] ?? null,
        ]);

        // Crédito de pontos (se houver regra ativa)
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
}
