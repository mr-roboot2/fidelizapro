<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Cupom;
use App\Models\Parceiro;
use App\Services\CupomService;
use Illuminate\Http\Request;

class BeneficioController extends Controller
{
    public function listar(Request $request)
    {
        $cliente = $request->user();

        $parceiros = Parceiro::where('empresa_id', $cliente->empresa_id)
            ->where('ativo', true)
            ->with(['beneficios' => fn($q) => $q->where('ativo', true)
                ->orderByDesc('destaque')->orderBy('nome')])
            ->orderBy('nome')
            ->get();

        $resultado = $parceiros->map(function ($p) use ($cliente) {
            return [
                'id' => $p->id,
                'nome' => $p->nome,
                'descricao' => $p->descricao,
                'categoria' => $p->categoria,
                'logo' => $p->logo ? asset('storage/'.$p->logo) : null,
                'endereco' => $p->endereco,
                'telefone' => $p->telefone,
                'site' => $p->site,
                'beneficios' => $p->beneficios->map(fn($b) => [
                    'id' => $b->id,
                    'nome' => $b->nome,
                    'descricao' => $b->descricao,
                    'tipo' => $b->tipo,
                    'tipo_descricao' => $b->descricaoTipo(),
                    'valor' => (float) $b->valor,
                    'condicoes' => $b->condicoes,
                    'valido_ate' => $b->valido_ate?->format('d/m/Y'),
                    'destaque' => (bool) $b->destaque,
                    'pode_resgatar' => $b->podeResgatarPor($cliente),
                    'restantes_para_voce' => $b->limite_por_cliente
                        ? max(0, $b->limite_por_cliente - $b->quantidadeJaResgatadaPor($cliente))
                        : null,
                ])->values(),
            ];
        })->filter(fn($p) => count($p['beneficios']) > 0)->values();

        return response()->json(['parceiros' => $resultado]);
    }

    public function gerarCupom(Request $request, CupomService $service)
    {
        $dados = $request->validate(['beneficio_id' => 'required|exists:beneficios,id']);
        $beneficio = Beneficio::findOrFail($dados['beneficio_id']);

        try {
            $cupom = $service->gerar($request->user(), $beneficio);
            return response()->json([
                'message' => 'Cupom ativado!',
                'cupom' => [
                    'codigo' => $cupom->codigo,
                    'valido_ate' => $cupom->valido_ate->format('d/m/Y H:i'),
                    'beneficio' => $beneficio->nome,
                    'parceiro' => $beneficio->parceiro->nome,
                ],
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function meusCupons(Request $request)
    {
        $cupons = Cupom::where('cliente_id', $request->user()->id)
            ->with('beneficio.parceiro')
            ->latest()->take(50)->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'beneficio' => $c->beneficio->nome,
                'parceiro' => $c->beneficio->parceiro->nome,
                'parceiro_logo' => $c->beneficio->parceiro->logo ? asset('storage/'.$c->beneficio->parceiro->logo) : null,
                'tipo_descricao' => $c->beneficio->descricaoTipo(),
                'status' => $c->status,
                'utilizavel' => $c->utilizavel(),
                'valido_ate' => $c->valido_ate->format('d/m/Y H:i'),
                'usado_em' => $c->usado_em?->format('d/m/Y H:i'),
            ]);

        return response()->json(['cupons' => $cupons]);
    }
}
