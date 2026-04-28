<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransacaoPonto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransacaoController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = TransacaoPonto::with('cliente')->where('empresa_id', $empresaId);

        if ($tipo = $request->input('tipo')) $query->where('tipo', $tipo);
        if ($origem = $request->input('origem')) $query->where('origem', $origem);
        if ($cli = $request->input('cliente_id')) $query->where('cliente_id', $cli);

        $transacoes = $query->latest()->paginate(30)->withQueryString();
        return view('admin.transacoes.index', compact('transacoes'));
    }
}
