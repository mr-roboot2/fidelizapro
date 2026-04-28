<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegraPontuacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegraPontuacaoController extends Controller
{
    public function index()
    {
        $regras = RegraPontuacao::where('empresa_id', Auth::user()->empresa_id)
            ->orderBy('tipo')->orderBy('nome')->get();
        return view('admin.regras.index', compact('regras'));
    }

    public function create()
    {
        return view('admin.regras.form', ['regra' => new RegraPontuacao()]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['empresa_id'] = Auth::user()->empresa_id;
        $dados['ativo'] = $request->boolean('ativo', true);
        RegraPontuacao::create($dados);
        return redirect()->route('admin.regras.index')->with('success', 'Regra criada!');
    }

    public function edit(RegraPontuacao $regra)
    {
        $this->autorizar($regra);
        return view('admin.regras.form', compact('regra'));
    }

    public function update(Request $request, RegraPontuacao $regra)
    {
        $this->autorizar($regra);
        $dados = $this->validar($request);
        $dados['ativo'] = $request->boolean('ativo');
        $regra->update($dados);
        return redirect()->route('admin.regras.index')->with('success', 'Regra atualizada!');
    }

    public function destroy(RegraPontuacao $regra)
    {
        $this->autorizar($regra);
        $regra->delete();
        return redirect()->route('admin.regras.index')->with('success', 'Regra removida.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:compra,aniversario,indicacao,primeira_compra,cadastro,avaliacao',
            'valor_minimo' => 'nullable|numeric|min:0',
            'valor_maximo' => 'nullable|numeric|min:0',
            'pontos_por_real' => 'nullable|numeric|min:0',
            'multiplicador' => 'nullable|numeric|min:1',
            'pontos_fixos' => 'nullable|integer|min:0',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        ]);
    }

    protected function autorizar(RegraPontuacao $regra): void
    {
        abort_if($regra->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
