<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Recompensa;
use App\Models\Roleta;
use App\Models\RoletaCredito;
use App\Models\RoletaGatilho;
use App\Models\RoletaGatilhoDisparo;
use App\Models\RoletaGiro;
use App\Models\RoletaPremio;
use App\Services\RoletaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoletaController extends Controller
{
    public function __construct(private RoletaService $roletaService) {}

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $roleta = Roleta::firstOrCreate(['empresa_id' => $empresaId]);
        $this->garantirGatilhosPadrao($roleta);
        $roleta->load(['premios.recompensa', 'gatilhos']);
        $recompensas = Recompensa::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
        $totalGiros = $roleta->giros()->count();
        $girosHoje  = $roleta->giros()->whereDate('executado_em', now()->toDateString())->count();
        $gatilhosPorTipo = $roleta->gatilhos->keyBy('tipo');

        return view('admin.roleta.index', compact('roleta', 'recompensas', 'totalGiros', 'girosHoje', 'gatilhosPorTipo'));
    }

    public function metricas()
    {
        $empresaId = Auth::user()->empresa_id;
        $roleta = Roleta::firstOrCreate(['empresa_id' => $empresaId]);

        $hoje = now()->toDateString();
        $inicioSemana = now()->subDays(6)->toDateString();
        $inicio30d = now()->subDays(29)->toDateString();

        $girosQuery = RoletaGiro::where('roleta_id', $roleta->id);

        $kpi = [
            'total'           => (clone $girosQuery)->count(),
            'hoje'            => (clone $girosQuery)->whereDate('executado_em', $hoje)->count(),
            'semana'          => (clone $girosQuery)->whereDate('executado_em', '>=', $inicioSemana)->count(),
            'pontos_total'    => (int) (clone $girosQuery)->sum('pontos_concedidos'),
            'pontos_hoje'     => (int) (clone $girosQuery)->whereDate('executado_em', $hoje)->sum('pontos_concedidos'),
            'recompensas'     => (clone $girosQuery)->whereNotNull('resgate_id')->count(),
            'saldo_pendente'  => (int) RoletaCredito::where('roleta_id', $roleta->id)->sum('giros_disponiveis'),
            'clientes_unicos' => (clone $girosQuery)->distinct('cliente_id')->count('cliente_id'),
        ];

        // Série temporal 30d (sempre 30 pontos, dias vazios = 0)
        $serieDb = (clone $girosQuery)
            ->whereDate('executado_em', '>=', $inicio30d)
            ->selectRaw('DATE(executado_em) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $serie = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $serie[] = ['data' => $d, 'total' => (int) ($serieDb[$d] ?? 0)];
        }

        $distribuicao = (clone $girosQuery)
            ->selectRaw('tipo_resultado, COUNT(*) as total')
            ->groupBy('tipo_resultado')
            ->pluck('total', 'tipo_resultado');

        $topPremios = (clone $girosQuery)
            ->whereNotNull('roleta_premio_id')
            ->selectRaw('roleta_premio_id, COUNT(*) as total')
            ->groupBy('roleta_premio_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($g) use ($roleta) {
                $p = $roleta->premios->firstWhere('id', $g->roleta_premio_id);
                return [
                    'label' => $p->label ?? '(removido)',
                    'cor'   => $p->cor ?? '#94a3b8',
                    'total' => (int) $g->total,
                ];
            });

        $topGanhadores = (clone $girosQuery)
            ->selectRaw('cliente_id, COUNT(*) as giros, SUM(pontos_concedidos) as pontos')
            ->groupBy('cliente_id')
            ->orderByDesc('giros')
            ->limit(10)
            ->with('cliente:id,nome,telefone')
            ->get()
            ->map(fn ($g) => [
                'nome'     => $g->cliente->nome ?? '(removido)',
                'telefone' => $g->cliente->telefone ?? '',
                'giros'    => (int) $g->giros,
                'pontos'   => (int) $g->pontos,
            ]);

        $gatilhosDisparados = RoletaGatilhoDisparo::where('roleta_id', $roleta->id)
            ->where('created_at', '>=', $inicio30d.' 00:00:00')
            ->selectRaw('tipo, COUNT(*) as total, SUM(giros_creditados) as giros')
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get();

        $roleta->loadMissing('premios');

        return view('admin.roleta.metricas', compact(
            'roleta', 'kpi', 'serie', 'distribuicao', 'topPremios', 'topGanhadores', 'gatilhosDisparados'
        ));
    }

    public function gatilhoSalvar(Request $request, Roleta $roleta)
    {
        $this->autorizar($roleta);
        $dados = $request->validate([
            'tipo'  => 'required|in:'.implode(',', array_keys(RoletaGatilho::TIPOS)),
            'valor' => 'nullable|integer|min:1|max:100000',
            'giros' => 'required|integer|min:1|max:50',
            'ativo' => 'nullable|boolean',
        ]);
        $dados['ativo'] = $request->boolean('ativo');

        RoletaGatilho::updateOrCreate(
            ['roleta_id' => $roleta->id, 'tipo' => $dados['tipo']],
            ['valor' => $dados['valor'] ?? null, 'giros' => $dados['giros'], 'ativo' => $dados['ativo']]
        );

        return back()->with('success', 'Gatilho salvo!');
    }

    public function update(Request $request, Roleta $roleta)
    {
        $this->autorizar($roleta);
        $dados = $request->validate([
            'nome'                => 'required|string|max:120',
            'ativa'               => 'nullable|boolean',
            'tempo_min_ms'        => 'required|integer|min:1500|max:15000',
            'tempo_max_ms'        => 'required|integer|min:1500|max:15000|gte:tempo_min_ms',
            'mensagem_consolacao'  => 'required|string|max:255',
            'mensagem_pontos'      => 'required|string|max:255',
            'mensagem_recompensa'  => 'required|string|max:255',
            'mensagem_nova_chance' => 'required|string|max:255',
            'pontos_consolacao'    => 'required|integer|min:0|max:255',
            'limite_giros_dia'        => 'required|integer|min:1|max:50',
            'limite_giros_dia_por_ip' => 'nullable|integer|min:1|max:200',
            'validade_dias'           => 'nullable|integer|min:1|max:365',
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
        $this->roletaService->creditar($roleta, $cliente, $dados['giros'], 'manual', null, 'cortesia da loja');
        return back()->with('success', "Creditado {$dados['giros']} giro(s) para {$cliente->nome}.");
    }

    protected function validarPremio(Request $request, Roleta $roleta): array
    {
        $empresaId = Auth::user()->empresa_id;

        return $request->validate([
            'ordem'              => 'required|integer|min:0|max:255',
            'label'              => 'required|string|max:60',
            'cor'                => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'tipo'               => 'required|in:'.implode(',', array_keys(RoletaPremio::TIPOS)),
            // IDOR cross-tenant: recompensa precisa pertencer à mesma empresa
            'recompensa_id'      => ['nullable', 'required_if:tipo,recompensa',
                                     Rule::exists('recompensas', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId))],
            'pontos'             => 'nullable|integer|min:1|required_if:tipo,pontos',
            'peso'               => 'required|integer|min:0|max:1000',
            'quantidade_max_dia' => 'nullable|integer|min:1|max:1000',
            'tier_minimo_pontos' => 'nullable|integer|min:1|max:1000000',
            'valido_de'          => 'nullable|date',
            'valido_ate'         => 'nullable|date|after_or_equal:valido_de',
        ]);
    }

    protected function garantirGatilhosPadrao(Roleta $roleta): void
    {
        foreach (array_keys(RoletaGatilho::TIPOS) as $tipo) {
            RoletaGatilho::firstOrCreate(
                ['roleta_id' => $roleta->id, 'tipo' => $tipo],
                ['giros' => 1, 'ativo' => $tipo === 'primeiro_cadastro']
            );
        }
    }

    protected function autorizar(Roleta $roleta): void
    {
        abort_if($roleta->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
