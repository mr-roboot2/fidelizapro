<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recompensa;
use App\Services\ResgateService;
use Illuminate\Http\Request;

class ResgateController extends Controller
{
    public function index(Request $request)
    {
        $resgates = $request->user()->resgates()->with('recompensa')->latest()->take(50)->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'codigo' => $r->codigo,
                'recompensa' => $r->recompensa->nome,
                'pontos_usados' => $r->pontos_usados,
                'status' => $r->status,
                'data' => $r->created_at->format('d/m/Y H:i'),
                'aprovado_em' => $r->aprovado_em?->format('d/m/Y H:i'),
                'entregue_em' => $r->entregue_em?->format('d/m/Y H:i'),
                'expira_em' => $r->expira_em?->format('d/m/Y'),
                'expira_em_iso' => $r->expira_em?->toIso8601String(),
                'expirado' => $r->expirado(),
            ]);

        return response()->json(['resgates' => $resgates]);
    }

    public function solicitar(Request $request, ResgateService $service)
    {
        $dados = $request->validate([
            'recompensa_id' => 'required|exists:recompensas,id',
            'observacao' => 'nullable|string',
        ]);

        $cliente = $request->user();
        $recompensa = Recompensa::findOrFail($dados['recompensa_id']);

        try {
            $resgate = $service->solicitar($cliente, $recompensa, $dados['observacao'] ?? null, $request->ip());
            return response()->json([
                'message' => 'Resgate solicitado! Aguarde aprovação da empresa.',
                'resgate' => [
                    'id' => $resgate->id,
                    'codigo' => $resgate->codigo,
                    'pontos_usados' => $resgate->pontos_usados,
                    'status' => $resgate->status,
                ],
                'novo_saldo_pontos' => (float) $cliente->fresh()->pontos_atual,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
