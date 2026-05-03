<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Automacao;
use App\Services\AutomacaoService;
use Illuminate\Http\Request;

class AutomacaoController extends Controller
{
    public function index()
    {
        $automacoes = Automacao::all()->keyBy('tipo');

        $tipos = collect(Automacao::TIPOS)->map(function ($nome, $tipo) use ($automacoes) {
            return $automacoes->get($tipo) ?? new Automacao([
                'tipo' => $tipo, 'nome' => $nome,
                'mensagem' => Automacao::TEMPLATES_PADRAO[$tipo] ?? '',
                'ativo' => false,
            ]);
        });

        return view('super.automacoes.index', compact('tipos'));
    }

    public function create(Request $request)
    {
        $tipo = $request->input('tipo');
        if (!array_key_exists($tipo, Automacao::TIPOS)) {
            return redirect()->route('super.automacoes.index');
        }

        $automacao = new Automacao([
            'tipo' => $tipo,
            'nome' => Automacao::TIPOS[$tipo],
            'mensagem' => Automacao::TEMPLATES_PADRAO[$tipo] ?? '',
            'ativo' => true,
            'dias_offset' => 7,
        ]);

        return view('super.automacoes.form', compact('automacao'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'tipo' => 'required|in:'.implode(',', array_keys(Automacao::TIPOS)),
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string|max:2000',
            'dias_offset' => 'nullable|integer|min:0|max:365',
            'ativo' => 'boolean',
        ]);

        if (Automacao::where('tipo', $dados['tipo'])->exists()) {
            return back()->with('error', 'Já existe uma automação deste tipo.');
        }

        $dados['ativo'] = $request->boolean('ativo', true);
        Automacao::create($dados);
        return redirect()->route('super.automacoes.index')->with('success', 'Automação criada!');
    }

    public function edit(Automacao $automacao)
    {
        return view('super.automacoes.form', compact('automacao'));
    }

    public function update(Request $request, Automacao $automacao)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string|max:2000',
            'dias_offset' => 'nullable|integer|min:0|max:365',
            'ativo' => 'boolean',
        ]);
        $dados['ativo'] = $request->boolean('ativo');
        $automacao->update($dados);
        return redirect()->route('super.automacoes.index')->with('success', 'Automação atualizada!');
    }

    public function toggle(Automacao $automacao)
    {
        $automacao->update(['ativo' => !$automacao->ativo]);
        return back()->with('success', $automacao->ativo ? 'Automação ativada' : 'Automação pausada');
    }

    public function executarAgora(Automacao $automacao, AutomacaoService $service)
    {
        $r = $service->executarUma($automacao);
        return back()->with('success',
            "Executado: {$r['enviados']} mensagens enviadas, {$r['falhas']} falhas (de {$r['total']} alvos)."
        );
    }

    public function destroy(Automacao $automacao)
    {
        $automacao->delete();
        return back()->with('success', 'Automação removida.');
    }
}
