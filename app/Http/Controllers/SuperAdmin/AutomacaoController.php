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
        $automacoes = Automacao::orderBy('id')->get();
        $porTipo = $automacoes->where('personalizada', false)->keyBy('tipo');
        $personalizadas = $automacoes->where('personalizada', true)->values();

        // Cards dos tipos fixos (1 por tipo, sem personalizada)
        $tiposFixos = collect(Automacao::TIPOS)
            ->except('personalizada')
            ->map(function ($nome, $tipo) use ($porTipo) {
                return $porTipo->get($tipo) ?? new Automacao([
                    'tipo' => $tipo, 'nome' => $nome,
                    'mensagem' => Automacao::TEMPLATES_PADRAO[$tipo] ?? '',
                    'ativo' => false,
                ]);
            });

        return view('super.automacoes.index', compact('tiposFixos', 'personalizadas'));
    }

    public function create(Request $request)
    {
        $tipo = $request->input('tipo');
        if (!array_key_exists($tipo, Automacao::TIPOS)) {
            return redirect()->route('super.automacoes.index');
        }

        $personalizada = $tipo === 'personalizada';

        $automacao = new Automacao([
            'tipo' => $tipo,
            'personalizada' => $personalizada,
            'nome' => $personalizada ? '' : Automacao::TIPOS[$tipo],
            'mensagem' => $personalizada ? '' : (Automacao::TEMPLATES_PADRAO[$tipo] ?? ''),
            'ativo' => true,
            'dias_offset' => 7,
            'gatilho' => $personalizada ? 'manual' : null,
        ]);

        return view('super.automacoes.form', compact('automacao'));
    }

    public function store(Request $request)
    {
        $dados = $this->validarDados($request);

        $dados['personalizada'] = $dados['tipo'] === 'personalizada';

        // Tipos fixos: 1 registro por tipo. Personalizadas: pode ter várias.
        if (!$dados['personalizada'] && Automacao::where('tipo', $dados['tipo'])->where('personalizada', false)->exists()) {
            return back()->with('error', 'Já existe uma automação deste tipo.')->withInput();
        }

        if (!$dados['personalizada']) {
            $dados['gatilho'] = null;
            $dados['valor_referencia'] = null;
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
        $dados = $this->validarDados($request, $automacao);
        $dados['ativo'] = $request->boolean('ativo');
        $automacao->update($dados);
        return redirect()->route('super.automacoes.index')->with('success', 'Automação atualizada!');
    }

    /**
     * Valida e devolve os campos. Em update, $automacaoExistente garante que
     * o tipo não seja alterado (não é editável).
     */
    protected function validarDados(Request $request, ?Automacao $automacaoExistente = null): array
    {
        $regras = [
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string|max:2000',
            'dias_offset' => 'nullable|integer|min:0|max:3650',
            'valor_referencia' => 'nullable|numeric|min:0',
            'ativo' => 'boolean',
        ];

        if (!$automacaoExistente) {
            $regras['tipo'] = 'required|in:'.implode(',', array_keys(Automacao::TIPOS));
        }

        $tipo = $automacaoExistente?->tipo ?? $request->input('tipo');
        if ($tipo === 'personalizada') {
            $regras['gatilho'] = 'required|in:'.implode(',', array_keys(Automacao::GATILHOS));
        }

        return $request->validate($regras);
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
