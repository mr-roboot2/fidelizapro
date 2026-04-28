<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Cupom;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ParceiroController extends Controller
{
    public function index()
    {
        $parceiros = Parceiro::where('empresa_id', Auth::user()->empresa_id)
            ->withCount(['beneficios as beneficios_ativos_count' => fn($q) => $q->where('ativo', true)])
            ->orderBy('nome')
            ->paginate(20);

        return view('admin.parceiros.index', compact('parceiros'));
    }

    public function create()
    {
        return view('admin.parceiros.form', ['parceiro' => new Parceiro()]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['empresa_id'] = Auth::user()->empresa_id;
        $dados['ativo'] = $request->boolean('ativo', true);
        if ($request->hasFile('logo')) {
            $dados['logo'] = $request->file('logo')->store('parceiros', 'public');
        }
        Parceiro::create($dados);
        return redirect()->route('admin.parceiros.index')->with('success', 'Parceiro cadastrado!');
    }

    public function show(Parceiro $parceiro)
    {
        $this->autorizar($parceiro);
        $parceiro->load('beneficios');

        $totalCupons = Cupom::whereHas('beneficio', fn($q) => $q->where('parceiro_id', $parceiro->id))->count();
        $cuponsUsados = Cupom::whereHas('beneficio', fn($q) => $q->where('parceiro_id', $parceiro->id))
            ->where('status', 'usado')->count();

        return view('admin.parceiros.show', compact('parceiro', 'totalCupons', 'cuponsUsados'));
    }

    public function edit(Parceiro $parceiro)
    {
        $this->autorizar($parceiro);
        return view('admin.parceiros.form', compact('parceiro'));
    }

    public function update(Request $request, Parceiro $parceiro)
    {
        $this->autorizar($parceiro);
        $dados = $this->validar($request);
        $dados['ativo'] = $request->boolean('ativo');
        if ($request->hasFile('logo')) {
            if ($parceiro->logo) Storage::disk('public')->delete($parceiro->logo);
            $dados['logo'] = $request->file('logo')->store('parceiros', 'public');
        }
        $parceiro->update($dados);
        return redirect()->route('admin.parceiros.show', $parceiro)->with('success', 'Parceiro atualizado!');
    }

    public function destroy(Parceiro $parceiro)
    {
        $this->autorizar($parceiro);
        if ($parceiro->logo) Storage::disk('public')->delete($parceiro->logo);
        $parceiro->delete();
        return redirect()->route('admin.parceiros.index')->with('success', 'Parceiro removido.');
    }

    public function relatorio(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $de = $request->input('de', now()->subDays(30)->toDateString());
        $ate = $request->input('ate', now()->toDateString());

        $cupons = Cupom::whereHas('beneficio.parceiro', fn($q) => $q->where('empresa_id', $empresaId))
            ->with('beneficio.parceiro', 'cliente')
            ->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59'])
            ->latest()->paginate(30);

        $totalGerados = (clone $cupons)->total();
        $totalUsados = Cupom::whereHas('beneficio.parceiro', fn($q) => $q->where('empresa_id', $empresaId))
            ->where('status', 'usado')
            ->whereBetween('usado_em', [$de.' 00:00:00', $ate.' 23:59:59'])->count();

        return view('admin.parceiros.relatorio', compact('cupons', 'de', 'ate', 'totalGerados', 'totalUsados'));
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'categoria' => 'nullable|string|max:80',
            'logo' => 'nullable|image|max:1024',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'endereco' => 'nullable|string|max:255',
            'site' => 'nullable|url|max:255',
        ]);
    }

    protected function autorizar(Parceiro $p): void
    {
        abort_if($p->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
