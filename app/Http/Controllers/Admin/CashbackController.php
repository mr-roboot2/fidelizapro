<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MovimentoCashback;
use App\Services\CashbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashbackController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $movimentos = MovimentoCashback::with('cliente')
            ->where('empresa_id', $empresaId)
            ->latest()->paginate(30);

        $totalCreditado = MovimentoCashback::where('empresa_id', $empresaId)
            ->where('tipo', 'credito')->sum('valor');
        $totalUsado = MovimentoCashback::where('empresa_id', $empresaId)
            ->where('tipo', 'debito')->sum('valor');
        $emCirculacao = Cliente::where('empresa_id', $empresaId)->sum('cashback_atual');

        return view('admin.cashback.index', compact('movimentos', 'totalCreditado', 'totalUsado', 'emCirculacao'));
    }

    public function ajustar(Request $request, CashbackService $service)
    {
        $empresaId = Auth::user()->empresa_id;
        $dados = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo' => 'required|in:credito,debito',
            'valor' => 'required|numeric|min:0.01',
            'descricao' => 'nullable|string|max:255',
        ]);

        $cliente = Cliente::where('id', $dados['cliente_id'])->where('empresa_id', $empresaId)->firstOrFail();

        try {
            if ($dados['tipo'] === 'credito') {
                $service->creditar($cliente, $dados['valor'], 'manual', null, $dados['descricao']);
            } else {
                $service->debitar($cliente, $dados['valor'], 'manual', null, $dados['descricao']);
            }
            return back()->with('success', 'Cashback ajustado!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
