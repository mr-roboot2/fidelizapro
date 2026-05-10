<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Recompensa;
use App\Models\Roleta;
use App\Models\RoletaPremio;
use App\Services\RoletaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoletaController extends Controller
{
    public function __construct(private RoletaService $roletaService) {}

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $roleta = Roleta::firstOrCreate(['empresa_id' => $empresaId]);
        $roleta->load(['premios.recompensa']);
        $recompensas = Recompensa::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
        $totalGiros = $roleta->giros()->count();
        $girosHoje  = $roleta->giros()->whereDate('executado_em', now()->toDateString())->count();

        return view('admin.roleta.index', compact('roleta', 'recompensas', 'totalGiros', 'girosHoje'));
    }

    public function update(Request $request, Roleta $roleta)
    {
        $this->autorizar($roleta);
        $dados = $request->validate([
            'nome'                => 'required|string|max:120',
            'ativa'               => 'nullable|boolean',
            'tempo_min_ms'        => 'required|integer|min:1500|max:15000',
            'tempo_max_ms'        => 'required|integer|min:1500|max:15000|gte:tempo_min_ms',
            'mensagem_consolacao' => 'required|string|max:255',
            'pontos_consolacao'   => 'required|integer|min:0|max:255',
            'limite_giros_dia'    => 'required|integer|min:1|max:50',
        ]);
        $dados['ativa'] = $request->boolean('ativa');
        $roleta->update($dados);
        return back()->with('success', 'Roleta atualizada!');
    }

    public function premioStore(Request $request, Roleta $roleta)
    {
        $this->autorizar($roleta);
        $dados = $this->validarPremio($request, $roleta);
        $dados['roleta_id'] = $roleta->id;
        $dados['ativo'] = $request->boolean('ativo', true);
        RoletaPremio::create($dados);
        return back()->with('success', 'Prêmio adicionado!');
    }

    public function premioUpdate(Request $request, Roleta $roleta, RoletaPremio $premio)
    {
        $this->autorizar($roleta);
        abort_if($premio->roleta_id !== $roleta->id, 403);
        $dados = $this->validarPremio($request, $roleta);
        $dados['ativo'] = $request->boolean('ativo');
        $premio->update($dados);
        return back()->with('success', 'Prêmio atualizado!');
    }

    public function premioDestroy(Roleta $roleta, RoletaPremio $premio)
    {
        $this->autorizar($roleta);
        abort_if($premio->roleta_id !== $roleta->id, 403);
        $premio->delete();
        return back()->with('success', 'Prêmio removido.');
    }

    public function creditar(Request $request, Roleta $roleta)
    {
        $this->autorizar($roleta);
        $dados = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'giros'      => 'required|integer|min:1|max:50',
        ]);
        $cliente = Cliente::where('empresa_id', $roleta->empresa_id)
            ->findOrFail($dados['cliente_id']);
        $this->roletaService->creditar($roleta, $cliente, $dados['giros'], 'manual');
        return back()->with('success', "Creditado {$dados['giros']} giro(s) para {$cliente->nome}.");
    }

    protected function validarPremio(Request $request, Roleta $roleta): array
    {
        return $request->validate([
            'ordem'         => 'required|integer|min:0|max:255',
            'label'         => 'required|string|max:60',
            'cor'           => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'tipo'          => 'required|in:'.implode(',', array_keys(RoletaPremio::TIPOS)),
            'recompensa_id' => 'nullable|exists:recompensas,id|required_if:tipo,recompensa',
            'pontos'        => 'nullable|integer|min:1|required_if:tipo,pontos',
            'peso'          => 'required|integer|min:0|max:1000',
        ]);
    }

    protected function autorizar(Roleta $roleta): void
    {
        abort_if($roleta->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
