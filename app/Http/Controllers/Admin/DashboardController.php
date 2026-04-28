<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campanha;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\MovimentoCashback;
use App\Models\Resgate;
use App\Models\TransacaoPonto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;

        $totalClientes = Cliente::where('empresa_id', $empresaId)->count();
        $clientesAtivos = Cliente::where('empresa_id', $empresaId)->where('ativo', true)->count();
        $totalCompras = Compra::where('empresa_id', $empresaId)->count();
        $faturamento = Compra::where('empresa_id', $empresaId)->sum('valor');
        $ticketMedio = $totalCompras > 0 ? $faturamento / $totalCompras : 0;

        $pontosEmCirculacao = Cliente::where('empresa_id', $empresaId)->sum('pontos_atual');
        $cashbackEmCirculacao = Cliente::where('empresa_id', $empresaId)->sum('cashback_atual');

        $resgatesPendentes = Resgate::where('empresa_id', $empresaId)->where('status', 'pendente')->count();
        $totalResgates = Resgate::where('empresa_id', $empresaId)->count();

        $campanhasAtivas = Campanha::where('empresa_id', $empresaId)
            ->whereIn('status', ['agendada', 'enviando'])->count();

        $vendasUltimos7Dias = Compra::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as dia'), DB::raw('SUM(valor) as total'), DB::raw('COUNT(*) as qtd'))
            ->groupBy('dia')->orderBy('dia')->get();

        $topClientes = Cliente::where('empresa_id', $empresaId)
            ->orderByDesc('total_gasto')->take(5)->get();

        $resgatesRecentes = Resgate::with(['cliente', 'recompensa'])
            ->where('empresa_id', $empresaId)
            ->latest()->take(5)->get();

        $comprasRecentes = Compra::with('cliente')
            ->where('empresa_id', $empresaId)
            ->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalClientes', 'clientesAtivos', 'totalCompras', 'faturamento',
            'ticketMedio', 'pontosEmCirculacao', 'cashbackEmCirculacao',
            'resgatesPendentes', 'totalResgates', 'campanhasAtivas',
            'vendasUltimos7Dias', 'topClientes', 'resgatesRecentes', 'comprasRecentes'
        ));
    }
}
