<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Pesquisa;
use App\Models\Resgate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AIGrowthController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        Auth::user()->empresa?->marcarPassoVisto('ai_growth');
        [$de, $ate] = $this->parsePeriodo($request);
        return view('admin.ai_growth.index', $this->dados($empresaId, $de, $ate));
    }

    public function exportPdf(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        [$de, $ate] = $this->parsePeriodo($request);
        $dados = $this->dados($empresaId, $de, $ate);
        $dados['empresaNome'] = Auth::user()->empresa->nome;

        $pdf = Pdf::loadView('admin.ai_growth.pdf', $dados)->setPaper('a4', 'portrait');
        return $pdf->download('ai-growth-'.$de->format('Y-m-d').'-a-'.$ate->format('Y-m-d').'.pdf');
    }

    public function exportCsv(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        [$de, $ate] = $this->parsePeriodo($request);

        $compras = Compra::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de->copy()->startOfDay(), $ate->copy()->endOfDay()])
            ->with('cliente:id,nome,telefone')
            ->orderBy('created_at')
            ->get();

        $filename = "vendas-{$de->format('Y-m-d')}-a-{$ate->format('Y-m-d')}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($compras) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Hora', 'Cliente', 'Telefone', 'Valor (R$)', 'Pontos', 'Cashback (R$)', 'Origem', 'Descrição'], ';');
            foreach ($compras as $c) {
                fputcsv($out, [
                    $c->created_at->format('d/m/Y'),
                    $c->created_at->format('H:i'),
                    $c->cliente->nome ?? '—',
                    $c->cliente->telefone ?? '',
                    number_format($c->valor, 2, ',', '.'),
                    number_format($c->pontos_gerados, 2, ',', '.'),
                    number_format($c->cashback_gerado, 2, ',', '.'),
                    $c->origem,
                    $c->descricao,
                ], ';');
            }
            fclose($out);
        }, 200, $headers);
    }

    private function parsePeriodo(Request $request): array
    {
        try {
            $de = Carbon::parse($request->input('de', now()->startOfMonth()->toDateString()))->startOfDay();
            $ate = Carbon::parse($request->input('ate', now()->toDateString()))->startOfDay();
            if ($ate->lt($de)) [$de, $ate] = [$ate, $de];
        } catch (\Throwable $e) {
            $de = now()->startOfMonth();
            $ate = now()->startOfDay();
        }
        return [$de, $ate];
    }

    /**
     * Toda a agregação num único método pra reuso entre view, PDF e CSV.
     */
    private function dados(int $empresaId, Carbon $de, Carbon $ate): array
    {
        $iniRange = $de->copy()->startOfDay();
        $fimRange = $ate->copy()->endOfDay();

        // Período anterior do mesmo tamanho pra comparativo
        $diasPeriodo = max(1, $de->diffInDays($ate) + 1);
        $iniAnt = $de->copy()->subDays($diasPeriodo)->startOfDay();
        $fimAnt = $de->copy()->subDay()->endOfDay();

        // KPIs do período
        $compras = Compra::where('empresa_id', $empresaId)->whereBetween('created_at', [$iniRange, $fimRange]);
        $kpi = [
            'faturamento'      => (float) (clone $compras)->sum('valor'),
            'vendas'           => (clone $compras)->count(),
            'clientes_unicos'  => (clone $compras)->distinct('cliente_id')->count('cliente_id'),
            'pontos_gerados'   => (float) (clone $compras)->sum('pontos_gerados'),
            'cashback_gerado'  => (float) (clone $compras)->sum('cashback_gerado'),
            'novos_clientes'   => Cliente::where('empresa_id', $empresaId)->whereBetween('created_at', [$iniRange, $fimRange])->count(),
            'resgates'         => Resgate::where('empresa_id', $empresaId)->whereBetween('created_at', [$iniRange, $fimRange])->count(),
        ];
        $kpi['ticket_medio'] = $kpi['vendas'] > 0 ? round($kpi['faturamento'] / $kpi['vendas'], 2) : 0;

        // Comparativo período anterior (mesma duração)
        $fatAnt = (float) Compra::where('empresa_id', $empresaId)->whereBetween('created_at', [$iniAnt, $fimAnt])->sum('valor');
        $vendasAnt = Compra::where('empresa_id', $empresaId)->whereBetween('created_at', [$iniAnt, $fimAnt])->count();
        $delta = [
            'faturamento' => $fatAnt > 0 ? round(($kpi['faturamento'] - $fatAnt) / $fatAnt * 100, 1) : null,
            'vendas'      => $vendasAnt > 0 ? round(($kpi['vendas'] - $vendasAnt) / $vendasAnt * 100, 1) : null,
        ];

        // NPS
        $nps = $this->calcularNps($empresaId, $iniRange, $fimRange);

        // Série diária
        $serieDb = Compra::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$iniRange, $fimRange])
            ->selectRaw('DATE(created_at) as dia, SUM(valor) as total, COUNT(*) as vendas')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $serie = [];
        for ($d = $de->copy(); $d->lte($ate); $d->addDay()) {
            $key = $d->toDateString();
            $row = $serieDb[$key] ?? null;
            $serie[] = [
                'dia'    => $d->format('d/m'),
                'data'   => $key,
                'total'  => (float) ($row->total ?? 0),
                'vendas' => (int) ($row->vendas ?? 0),
            ];
        }

        // Top 5 dias melhores e piores
        $diasComVendas = collect($serie)->filter(fn ($d) => $d['vendas'] > 0)->values();
        $topDias    = $diasComVendas->sortByDesc('total')->take(5)->values();
        $bottomDias = $diasComVendas->sortBy('total')->take(5)->values();

        // Faturamento médio por dia da semana (últimos 90 dias pra ter amostra)
        $diasSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $porDow = Compra::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('DAYOFWEEK(created_at) - 1 as dow, SUM(valor) as total, COUNT(*) as vendas, COUNT(DISTINCT DATE(created_at)) as dias_distintos')
            ->groupBy('dow')
            ->get()
            ->keyBy('dow');
        $vendasPorDow = [];
        for ($i = 0; $i < 7; $i++) {
            $r = $porDow[$i] ?? null;
            $dias = (int) ($r->dias_distintos ?? 0);
            $vendasPorDow[] = [
                'nome'         => $diasSemana[$i],
                'media_dia'    => $dias > 0 ? round((float) $r->total / $dias, 2) : 0,
                'total_vendas' => (int) ($r->vendas ?? 0),
            ];
        }

        // Top 10 clientes do período (por gasto)
        $topClientesPeriodo = Compra::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$iniRange, $fimRange])
            ->selectRaw('cliente_id, SUM(valor) as total, COUNT(*) as qtd')
            ->groupBy('cliente_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('cliente:id,nome,telefone')
            ->get();

        // Top clientes todos os tempos (snapshot)
        $topClientesAll = Cliente::where('empresa_id', $empresaId)
            ->orderByDesc('total_gasto')
            ->limit(10)
            ->get(['id', 'nome', 'telefone', 'total_gasto', 'total_compras', 'pontos_atual']);

        // Novos clientes — 12 meses
        $inicioAno = now()->subMonths(11)->startOfMonth();
        $novosDb = Cliente::where('empresa_id', $empresaId)
            ->where('created_at', '>=', $inicioAno)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');
        $novosMensal = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $novosMensal[] = ['mes' => $m, 'total' => (int) ($novosDb[$m] ?? 0)];
        }

        // Distribuição por faixa etária
        $faixas = ['18-25', '26-35', '36-45', '46-55', '56+', 'sem idade'];
        $idadeDb = Cliente::where('empresa_id', $empresaId)
            ->selectRaw('CASE
                WHEN data_nascimento IS NULL THEN 5
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 18 AND 25 THEN 0
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 26 AND 35 THEN 1
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 36 AND 45 THEN 2
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 46 AND 55 THEN 3
                ELSE 4
            END as faixa, COUNT(*) as total')
            ->groupBy('faixa')
            ->pluck('total', 'faixa');
        $distIdade = [];
        foreach ($faixas as $i => $f) {
            $distIdade[] = ['faixa' => $f, 'total' => (int) ($idadeDb[$i] ?? 0)];
        }

        // Retenção
        $totalClientes = Cliente::where('empresa_id', $empresaId)->count();
        $recorrentes   = Cliente::where('empresa_id', $empresaId)->where('total_compras', '>=', 2)->count();
        $retencao = $totalClientes > 0 ? round($recorrentes / $totalClientes * 100, 1) : 0;

        // Vendas detalhadas do período (pra impressão/PDF)
        $vendasDetalhadas = Compra::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$iniRange, $fimRange])
            ->with(['cliente:id,nome,telefone', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Resgates do período (com auditoria: aprovador + entregador)
        $resgatesDetalhados = Resgate::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$iniRange, $fimRange])
            ->with(['cliente:id,nome,telefone', 'recompensa:id,nome', 'aprovador:id,name', 'entregador:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return compact(
            'de', 'ate', 'kpi', 'delta', 'nps', 'serie',
            'topDias', 'bottomDias', 'vendasPorDow',
            'topClientesPeriodo', 'topClientesAll',
            'novosMensal', 'distIdade',
            'totalClientes', 'recorrentes', 'retencao',
            'vendasDetalhadas', 'resgatesDetalhados'
        );
    }

    private function calcularNps(int $empresaId, Carbon $ini, Carbon $fim): array
    {
        $pesquisas = Pesquisa::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$ini, $fim])
            ->get();
        $total = $pesquisas->count();
        if ($total === 0) return ['total' => 0, 'nps' => 0, 'promotores' => 0, 'detratores' => 0];

        $promotores = $pesquisas->where('nota', '>=', 4)->count();
        $detratores = $pesquisas->where('nota', '<=', 2)->count();
        return [
            'total'      => $total,
            'nps'        => round((($promotores - $detratores) / $total) * 100, 1),
            'promotores' => $promotores,
            'detratores' => $detratores,
        ];
    }
}
