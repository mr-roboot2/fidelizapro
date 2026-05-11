<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Campanha;
use App\Models\Cliente;
use App\Models\Cobranca;
use App\Models\Compra;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\Resgate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmpresas = Empresa::count();
        $empresasAtivas = Empresa::where('ativo', true)->count();
        $totalUsers = User::whereNotNull('empresa_id')->count();
        $totalClientes = Cliente::count();
        $totalCompras = Compra::count();
        $faturamentoTotal = Compra::sum('valor');
        $totalResgates = Resgate::count();
        $totalCampanhas = Campanha::count();

        $vendasUltimos30Dias = Compra::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw('DATE(created_at) as dia'), DB::raw('SUM(valor) as total'))
            ->groupBy('dia')->orderBy('dia')->get();

        $rankingEmpresas = Empresa::withCount(['clientes', 'compras'])
            ->withSum('compras as faturamento', 'valor')
            ->orderByDesc('faturamento')
            ->take(10)->get();

        // === Métricas SaaS ===
        $mrr = (float) Assinatura::whereIn('status', ['ativa', 'trial'])->sum('valor_mensal');
        $arr = $mrr * 12;
        $inadimplentes = Assinatura::where('status', 'inadimplente')->count();
        $totalAssinaturas = Assinatura::whereIn('status', ['ativa', 'trial', 'inadimplente'])->count();
        $taxaInadimplencia = $totalAssinaturas > 0 ? round($inadimplentes / $totalAssinaturas * 100, 1) : 0;

        // MRR mês a mês (últimos 12 meses) baseado em cobranças pagas
        $inicio = now()->subMonths(11)->startOfMonth();
        $cobrancasPagas = Cobranca::where('status', 'pago')
            ->where('pago_em', '>=', $inicio)
            ->selectRaw("DATE_FORMAT(pago_em, '%Y-%m') as mes, SUM(valor) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $mrrMensal = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $mrrMensal[] = ['mes' => $m, 'total' => (float) ($cobrancasPagas[$m] ?? 0)];
        }

        // Novas assinaturas por mês
        $novasAssinaturas = Assinatura::where('created_at', '>=', $inicio)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');
        $novasMensal = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $novasMensal[] = ['mes' => $m, 'total' => (int) ($novasAssinaturas[$m] ?? 0)];
        }

        // Distribuição por plano (ativas + trial)
        $distribuicaoPlanos = Plano::leftJoin('assinaturas', function ($j) {
                $j->on('assinaturas.plano_id', '=', 'planos.id')
                  ->whereIn('assinaturas.status', ['ativa', 'trial']);
            })
            ->selectRaw('planos.nome, COUNT(assinaturas.id) as total, planos.preco_mensal')
            ->groupBy('planos.id', 'planos.nome', 'planos.preco_mensal')
            ->orderByDesc('total')
            ->get();

        return view('super.dashboard', compact(
            'totalEmpresas', 'empresasAtivas', 'totalUsers', 'totalClientes',
            'totalCompras', 'faturamentoTotal', 'totalResgates', 'totalCampanhas',
            'vendasUltimos30Dias', 'rankingEmpresas',
            'mrr', 'arr', 'inadimplentes', 'taxaInadimplencia',
            'mrrMensal', 'novasMensal', 'distribuicaoPlanos'
        ));
    }
}
