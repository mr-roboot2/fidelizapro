<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Compra;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = Compra::with('cliente')->where('empresa_id', $empresaId);

        if ($cli = $request->input('cliente_id')) $query->where('cliente_id', $cli);
        if ($de = $request->input('de')) $query->whereDate('created_at', '>=', $de);
        if ($ate = $request->input('ate')) $query->whereDate('created_at', '<=', $ate);

        $compras = $query->latest()->paginate(20)->withQueryString();
        $totalValor = (clone $query)->sum('valor');
        $totalPontos = (clone $query)->sum('pontos_gerados');

        return view('admin.compras.index', compact('compras', 'totalValor', 'totalPontos'));
    }

    public function create(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $clientes = Cliente::where('empresa_id', $empresaId)->where('ativo', true)
            ->orderBy('nome')->get(['id', 'nome', 'telefone']);
        $clienteSelecionado = $request->input('cliente_id');
        return view('admin.compras.form', compact('clientes', 'clienteSelecionado'));
    }

    public function store(Request $request, CompraService $service)
    {
        $empresaId = Auth::user()->empresa_id;
        $dados = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'valor' => 'required|numeric|min:0.01',
            'desconto' => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string|max:255',
            'codigo' => 'nullable|string|max:50',
        ]);

        $cliente = Cliente::where('id', $dados['cliente_id'])->where('empresa_id', $empresaId)->firstOrFail();
        $compra = $service->registrar($cliente, array_merge($dados, [
            'user_id' => Auth::id(),
            'origem' => 'manual',
        ]));

        return redirect()->route('admin.compras.index')
            ->with('success', "Compra registrada! Pontos gerados: {$compra->pontos_gerados}");
    }

    public function show(Compra $compra)
    {
        abort_if($compra->empresa_id !== Auth::user()->empresa_id, 403);
        $compra->load('cliente', 'user');
        return view('admin.compras.show', compact('compra'));
    }
}
