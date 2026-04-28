<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use App\Services\PlanoLimiteService;
use Illuminate\Support\Facades\Auth;

class MeuPlanoController extends Controller
{
    public function index(PlanoLimiteService $planos)
    {
        $empresa = Auth::user()->empresa;
        $consumo = $planos->consumo($empresa);
        $planosDisponiveis = Plano::where('ativo', true)->orderBy('ordem')->orderBy('preco_mensal')->get();

        return view('admin.meu_plano.index', compact('empresa', 'consumo', 'planosDisponiveis'));
    }
}
