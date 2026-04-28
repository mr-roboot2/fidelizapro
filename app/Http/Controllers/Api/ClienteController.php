<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function dashboard(Request $request)
    {
        $cliente = $request->user();

        return response()->json([
            'pontos' => (float) $cliente->pontos_atual,
            'cashback' => (float) $cliente->cashback_atual,
            'cashback_pendente' => (float) $cliente->cashback_pendente,
            'total_gasto' => (float) $cliente->total_gasto,
            'total_compras' => $cliente->total_compras,
            'ultima_compra' => $cliente->ultima_compra?->toDateTimeString(),
        ]);
    }

    public function historicoCompras(Request $request)
    {
        $compras = $request->user()->compras()->latest()->take(50)->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'data' => $c->created_at->toDateTimeString(),
                'data_formatada' => $c->created_at->format('d/m/Y H:i'),
                'valor' => (float) $c->valor,
                'pontos_gerados' => (float) $c->pontos_gerados,
                'cashback_gerado' => (float) $c->cashback_gerado,
                'descricao' => $c->descricao,
            ]);

        return response()->json(['compras' => $compras]);
    }

    public function extrato(Request $request)
    {
        $transacoes = $request->user()->transacoesPontos()->latest()->take(50)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'data' => $t->created_at->format('d/m/Y H:i'),
                'tipo' => $t->tipo,
                'origem' => $t->origem,
                'pontos' => (float) $t->pontos,
                'saldo_posterior' => (float) $t->saldo_posterior,
                'descricao' => $t->descricao,
            ]);

        return response()->json(['transacoes' => $transacoes]);
    }

    public function atualizarPerfil(Request $request)
    {
        $cliente = $request->user();
        $dados = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
        ]);

        $cliente->update($dados);
        return response()->json(['cliente' => $cliente->fresh(), 'message' => 'Perfil atualizado!']);
    }
}
