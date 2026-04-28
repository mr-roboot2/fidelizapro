<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\Request;

class PlanoController extends Controller
{
    public function index()
    {
        $planos = Plano::withCount('empresas')->orderBy('ordem')->orderBy('preco_mensal')->paginate(20);
        return view('super.planos.index', compact('planos'));
    }

    public function create()
    {
        return view('super.planos.form', ['plano' => new Plano(['ativo' => true])]);
    }

    public function store(Request $request)
    {
        Plano::create($this->validar($request));
        return redirect()->route('super.planos.index')->with('success', 'Plano criado!');
    }

    public function edit(Plano $plano)
    {
        return view('super.planos.form', compact('plano'));
    }

    public function update(Request $request, Plano $plano)
    {
        $plano->update($this->validar($request));
        return redirect()->route('super.planos.index')->with('success', 'Plano atualizado!');
    }

    public function destroy(Plano $plano)
    {
        if ($plano->empresas()->count() > 0) {
            return back()->with('error', 'Não é possível excluir um plano com empresas vinculadas.');
        }
        $plano->delete();
        return back()->with('success', 'Plano removido.');
    }

    protected function validar(Request $request): array
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco_mensal' => 'required|numeric|min:0',
            'limite_clientes' => 'nullable|integer|min:1',
            'limite_compras_mes' => 'nullable|integer|min:1',
            'limite_recompensas' => 'nullable|integer|min:1',
            'limite_parceiros' => 'nullable|integer|min:1',
            'limite_users' => 'nullable|integer|min:1',
            'limite_campanhas_mes' => 'nullable|integer|min:1',
            'whatsapp_ilimitado' => 'boolean',
            'automacoes_disponivel' => 'boolean',
            'parceiros_disponivel' => 'boolean',
            'white_label_disponivel' => 'boolean',
            'ativo' => 'boolean',
            'ordem' => 'nullable|integer',
        ]);

        foreach (['whatsapp_ilimitado', 'automacoes_disponivel', 'parceiros_disponivel', 'white_label_disponivel', 'ativo'] as $b) {
            $dados[$b] = $request->boolean($b);
        }

        return $dados;
    }
}
