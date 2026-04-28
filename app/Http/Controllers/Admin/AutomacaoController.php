<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automacao;
use App\Services\AutomacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutomacaoController extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $automacoes = Automacao::where('empresa_id', $empresaId)->get()->keyBy('tipo');

        // Garante que todos os tipos aparecem (mesmo sem ainda configurados)
        $tipos = collect(Automacao::TIPOS)->map(function ($nome, $tipo) use ($automacoes) {
            return $automacoes->get($tipo) ?? new Automacao([
                'tipo' => $tipo, 'nome' => $nome,
                'mensagem' => Automacao::TEMPLATES_PADRAO[$tipo] ?? '',
                'ativo' => false,
            ]);
        });

        return view('admin.automacoes.index', compact('tipos'));
    }

    public function create(Request $request)
    {
        $tipo = $request->input('tipo');
        if (!array_key_exists($tipo, Automacao::TIPOS)) {
            return redirect()->route('admin.automacoes.index');
        }

        $automacao = new Automacao([
            'tipo' => $tipo,
            'nome' => Automacao::TIPOS[$tipo],
            'mensagem' => Automacao::TEMPLATES_PADRAO[$tipo] ?? '',
            'ativo' => true,
            'dias_offset' => 7,
        ]);

        return view('admin.automacoes.form', compact('automacao'));
    }

    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $dados = $request->validate([
            'tipo' => 'required|in:'.implode(',', array_keys(Automacao::TIPOS)),
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string|max:2000',
            'dias_offset' => 'nullable|integer|min:0|max:365',
            'ativo' => 'boolean',
        ]);

        if (Automacao::where('empresa_id', $empresaId)->where('tipo', $dados['tipo'])->exists()) {
            return back()->with('error', 'Já existe uma automação deste tipo.');
        }

        $dados['empresa_id'] = $empresaId;
        $dados['ativo'] = $request->boolean('ativo', true);

        Automacao::create($dados);
        return redirect()->route('admin.automacoes.index')->with('success', 'Automação criada!');
    }

    public function edit(Automacao $automacao)
    {
        $this->autorizar($automacao);
        return view('admin.automacoes.form', compact('automacao'));
    }

    public function update(Request $request, Automacao $automacao)
    {
        $this->autorizar($automacao);
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string|max:2000',
            'dias_offset' => 'nullable|integer|min:0|max:365',
            'ativo' => 'boolean',
        ]);
        $dados['ativo'] = $request->boolean('ativo');
        $automacao->update($dados);
        return redirect()->route('admin.automacoes.index')->with('success', 'Automação atualizada!');
    }

    public function toggle(Automacao $automacao)
    {
        $this->autorizar($automacao);
        $automacao->update(['ativo' => !$automacao->ativo]);
        return back()->with('success', $automacao->ativo ? 'Automação ativada' : 'Automação pausada');
    }

    public function executarAgora(Automacao $automacao, AutomacaoService $service)
    {
        $this->autorizar($automacao);
        $r = $service->executarUma($automacao);
        return back()->with('success',
            "Executado: {$r['enviados']} mensagens enviadas, {$r['falhas']} falhas (de {$r['total']} alvos)."
        );
    }

    public function destroy(Automacao $automacao)
    {
        $this->autorizar($automacao);
        $automacao->delete();
        return back()->with('success', 'Automação removida.');
    }

    protected function autorizar(Automacao $automacao): void
    {
        abort_if($automacao->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
