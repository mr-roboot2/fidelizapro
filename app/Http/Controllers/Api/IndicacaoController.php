<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Indicacao;
use Illuminate\Http\Request;

class IndicacaoController extends Controller
{
    public function index(Request $request)
    {
        $cliente = $request->user();
        $indicacoes = Indicacao::where('cliente_indicador_id', $cliente->id)
            ->with('indicado:id,nome')->latest()->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'nome_indicado' => $i->indicado?->nome ?? $i->nome_indicado,
                'telefone' => $i->telefone_indicado,
                'status' => $i->status,
                'pontos_concedidos' => (float) $i->pontos_concedidos,
                'data' => $i->created_at->format('d/m/Y'),
            ]);

        return response()->json([
            'codigo_indicacao' => $cliente->codigo_indicacao,
            'link' => url('/app/?ref='.$cliente->codigo_indicacao),
            'total_indicacoes' => $indicacoes->count(),
            'total_convertidas' => $indicacoes->where('status', 'convertido')->count(),
            'total_pontos_ganhos' => $indicacoes->sum('pontos_concedidos'),
            'indicacoes' => $indicacoes,
        ]);
    }

    public function indicar(Request $request)
    {
        $dados = $request->validate([
            'nome_indicado' => 'required|string|max:255',
            'telefone_indicado' => 'required|string|max:20',
        ]);

        $cliente = $request->user();

        $indicacao = Indicacao::create([
            'empresa_id' => $cliente->empresa_id,
            'cliente_indicador_id' => $cliente->id,
            'nome_indicado' => $dados['nome_indicado'],
            'telefone_indicado' => $dados['telefone_indicado'],
            'status' => 'pendente',
        ]);

        return response()->json([
            'message' => 'Indicação registrada! Você receberá pontos quando seu amigo realizar a primeira compra.',
            'indicacao' => $indicacao,
        ], 201);
    }
}
