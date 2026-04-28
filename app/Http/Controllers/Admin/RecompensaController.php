<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recompensa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecompensaController extends Controller
{
    public function index()
    {
        $recompensas = Recompensa::where('empresa_id', Auth::user()->empresa_id)
            ->orderByDesc('destaque')->orderBy('custo_pontos')->paginate(20);
        return view('admin.recompensas.index', compact('recompensas'));
    }

    public function create()
    {
        return view('admin.recompensas.form', ['recompensa' => new Recompensa()]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['empresa_id'] = Auth::user()->empresa_id;
        $dados['destaque'] = $request->boolean('destaque');
        $dados['ativo'] = $request->boolean('ativo', true);

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('recompensas', 'public');
        }

        Recompensa::create($dados);
        return redirect()->route('admin.recompensas.index')->with('success', 'Recompensa criada!');
    }

    public function edit(Recompensa $recompensa)
    {
        $this->autorizar($recompensa);
        return view('admin.recompensas.form', compact('recompensa'));
    }

    public function update(Request $request, Recompensa $recompensa)
    {
        $this->autorizar($recompensa);
        $dados = $this->validar($request);
        $dados['destaque'] = $request->boolean('destaque');
        $dados['ativo'] = $request->boolean('ativo');

        if ($request->hasFile('imagem')) {
            if ($recompensa->imagem) Storage::disk('public')->delete($recompensa->imagem);
            $dados['imagem'] = $request->file('imagem')->store('recompensas', 'public');
        }

        $recompensa->update($dados);
        return redirect()->route('admin.recompensas.index')->with('success', 'Recompensa atualizada!');
    }

    public function destroy(Recompensa $recompensa)
    {
        $this->autorizar($recompensa);
        $recompensa->delete();
        return redirect()->route('admin.recompensas.index')->with('success', 'Recompensa removida.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|image|max:2048',
            'custo_pontos' => 'required|integer|min:1',
            'estoque' => 'nullable|integer|min:0',
            'tipo' => 'required|in:produto,desconto,servico,experiencia',
            'valor_estimado' => 'nullable|numeric|min:0',
            'valido_ate' => 'nullable|date|after:today',
        ]);
    }

    protected function autorizar(Recompensa $recompensa): void
    {
        abort_if($recompensa->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
