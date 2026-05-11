<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Recompensa;
use App\Models\Sorteio;
use App\Services\SorteioService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SorteioController extends Controller
{
    public function __construct(private SorteioService $sorteioService) {}

    public function index()
    {
        $sorteios = Sorteio::where('empresa_id', Auth::user()->empresa_id)
            ->withCount('bilhetes')
            ->orderByRaw("FIELD(status, 'ativo', 'planejado', 'sorteado', 'finalizado', 'cancelado')")
            ->orderByDesc('data_sorteio')
            ->paginate(20);
        return view('admin.sorteios.index', compact('sorteios'));
    }

    public function create()
    {
        $recompensas = Recompensa::where('empresa_id', Auth::user()->empresa_id)
            ->where('ativo', true)->orderBy('nome')->get();
        return view('admin.sorteios.form', ['sorteio' => new Sorteio(), 'recompensas' => $recompensas]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['empresa_id'] = Auth::user()->empresa_id;

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('sorteios', 'public');
        }

        Sorteio::create($dados);
        return redirect()->route('admin.sorteios.index')->with('success', 'Sorteio criado!');
    }

    public function show(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        $sorteio->load(['recompensa', 'vencedor']);
        $bilhetes = $sorteio->bilhetes()->with('cliente:id,nome,telefone')->latest('id')->paginate(50);
        $porCliente = $sorteio->bilhetes()
            ->selectRaw('cliente_id, COUNT(*) as total')
            ->groupBy('cliente_id')
            ->orderByDesc('total')
            ->with('cliente:id,nome,telefone')
            ->limit(10)
            ->get();
        return view('admin.sorteios.show', compact('sorteio', 'bilhetes', 'porCliente'));
    }

    public function edit(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        $recompensas = Recompensa::where('empresa_id', $sorteio->empresa_id)
            ->where('ativo', true)->orderBy('nome')->get();
        return view('admin.sorteios.form', compact('sorteio', 'recompensas'));
    }

    public function update(Request $request, Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        $dados = $this->validar($request);

        if ($request->hasFile('imagem')) {
            if ($sorteio->imagem) Storage::disk('public')->delete($sorteio->imagem);
            $dados['imagem'] = $request->file('imagem')->store('sorteios', 'public');
        }

        $sorteio->update($dados);
        return redirect()->route('admin.sorteios.show', $sorteio)->with('success', 'Sorteio atualizado!');
    }

    public function destroy(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        if ($sorteio->status === 'sorteado') {
            return back()->with('error', 'Sorteio já realizado não pode ser excluído.');
        }
        $sorteio->delete();
        return redirect()->route('admin.sorteios.index')->with('success', 'Sorteio removido.');
    }

    public function ativar(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        if (!in_array($sorteio->status, ['planejado', 'cancelado'])) {
            return back()->with('error', 'Só sorteios planejados ou cancelados podem ser ativados.');
        }
        $sorteio->update(['status' => 'ativo']);
        return back()->with('success', 'Sorteio ativo — agora aceita bilhetes!');
    }

    public function cancelar(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        $sorteio->update(['status' => 'cancelado']);
        return back()->with('success', 'Sorteio cancelado.');
    }

    public function finalizar(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        if ($sorteio->status !== 'sorteado') {
            return back()->with('error', 'Só sorteios já sorteados podem ser finalizados.');
        }
        $sorteio->update(['status' => 'finalizado']);
        return back()->with('success', 'Sorteio finalizado — não aparece mais no PWA dos clientes.');
    }

    public function sortear(Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        try {
            $this->sorteioService->sortear($sorteio);
            return back()->with('success', "Vencedor sorteado: {$sorteio->fresh()->vencedor->nome}!");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function creditarBilhete(Request $request, Sorteio $sorteio)
    {
        $this->autorizar($sorteio);
        $dados = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'quantidade' => 'required|integer|min:1|max:50',
        ]);
        $cliente = Cliente::where('empresa_id', $sorteio->empresa_id)
            ->findOrFail($dados['cliente_id']);

        $criados = 0;
        for ($i = 0; $i < $dados['quantidade']; $i++) {
            if ($this->sorteioService->criarBilhete($sorteio, $cliente, 'manual')) $criados++;
            else break;
        }
        return back()->with('success', "Criados {$criados} bilhete(s) para {$cliente->nome}.");
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nome'                     => 'required|string|max:120',
            'descricao'                => 'nullable|string',
            'imagem'                   => 'nullable|image|max:2048',
            'recompensa_id'            => 'nullable|exists:recompensas,id',
            'valor_estimado'           => 'nullable|numeric|min:0',
            'data_sorteio'             => 'required|date',
            'status'                   => 'required|in:planejado,ativo,sorteado,cancelado',
            'max_bilhetes_por_cliente' => 'nullable|integer|min:1|max:1000',
        ]);
    }

    protected function autorizar(Sorteio $sorteio): void
    {
        abort_if($sorteio->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
