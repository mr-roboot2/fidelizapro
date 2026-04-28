<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BeneficioController extends Controller
{
    public function create(Parceiro $parceiro)
    {
        $this->autorizarParceiro($parceiro);
        return view('admin.beneficios.form', [
            'parceiro' => $parceiro,
            'beneficio' => new Beneficio(['parceiro_id' => $parceiro->id]),
        ]);
    }

    public function store(Request $request, Parceiro $parceiro)
    {
        $this->autorizarParceiro($parceiro);
        $dados = $this->validar($request);
        $dados['parceiro_id'] = $parceiro->id;
        $dados['destaque'] = $request->boolean('destaque');
        $dados['ativo'] = $request->boolean('ativo', true);
        Beneficio::create($dados);
        return redirect()->route('admin.parceiros.show', $parceiro)->with('success', 'Benefício criado!');
    }

    public function edit(Beneficio $beneficio)
    {
        $this->autorizar($beneficio);
        return view('admin.beneficios.form', [
            'parceiro' => $beneficio->parceiro,
            'beneficio' => $beneficio,
        ]);
    }

    public function update(Request $request, Beneficio $beneficio)
    {
        $this->autorizar($beneficio);
        $dados = $this->validar($request);
        $dados['destaque'] = $request->boolean('destaque');
        $dados['ativo'] = $request->boolean('ativo');
        $beneficio->update($dados);
        return redirect()->route('admin.parceiros.show', $beneficio->parceiro)->with('success', 'Benefício atualizado!');
    }

    public function destroy(Beneficio $beneficio)
    {
        $this->autorizar($beneficio);
        $parceiro = $beneficio->parceiro;
        $beneficio->delete();
        return redirect()->route('admin.parceiros.show', $parceiro)->with('success', 'Benefício removido.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|in:'.implode(',', array_keys(Beneficio::TIPOS)),
            'valor' => 'nullable|numeric|min:0',
            'condicoes' => 'nullable|string',
            'valido_ate' => 'nullable|date|after:today',
            'limite_por_cliente' => 'nullable|integer|min:1',
            'limite_total' => 'nullable|integer|min:1',
        ]);
    }

    protected function autorizarParceiro(Parceiro $p): void
    {
        abort_if($p->empresa_id !== Auth::user()->empresa_id, 403);
    }

    protected function autorizar(Beneficio $b): void
    {
        $this->autorizarParceiro($b->parceiro);
    }
}
