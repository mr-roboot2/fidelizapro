<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Pesquisa;
use App\Models\Resgate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $de = $request->input('de', now()->subDays(30)->toDateString());
        $ate = $request->input('ate', now()->toDateString());

        $compras = Compra::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59']);

        $totalVendas = (clone $compras)->sum('valor');
        $totalCompras = (clone $compras)->count();
        $ticketMedio = $totalCompras > 0 ? $totalVendas / $totalCompras : 0;
        $totalPontosGerados = (clone $compras)->sum('pontos_gerados');
        $totalCashbackGerado = (clone $compras)->sum('cashback_gerado');

        $vendasPorDia = (clone $compras)
            ->select(DB::raw('DATE(created_at) as dia'),
                     DB::raw('SUM(valor) as total'),
                     DB::raw('COUNT(*) as qtd'))
            ->groupBy('dia')->orderBy('dia')->get();

        $topClientesPeriodo = Compra::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59'])
            ->select('cliente_id', DB::raw('SUM(valor) as total'), DB::raw('COUNT(*) as qtd'))
            ->groupBy('cliente_id')->orderByDesc('total')->take(10)
            ->with('cliente')->get();

        $resgatesPeriodo = Resgate::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59'])->count();

        $novosClientesPeriodo = Cliente::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59'])->count();

        $nps = $this->calcularNps($empresaId, $de, $ate);

        return view('admin.relatorios.index', compact(
            'de', 'ate', 'totalVendas', 'totalCompras', 'ticketMedio',
            'totalPontosGerados', 'totalCashbackGerado', 'vendasPorDia',
            'topClientesPeriodo', 'resgatesPeriodo', 'novosClientesPeriodo', 'nps'
        ));
    }

    protected function calcularNps(int $empresaId, string $de, string $ate): array
    {
        $pesquisas = Pesquisa::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de.' 00:00:00', $ate.' 23:59:59'])->get();
        $total = $pesquisas->count();
        if ($total === 0) return ['total' => 0, 'nps' => 0, 'promotores' => 0, 'detratores' => 0];

        $promotores = $pesquisas->where('nota', '>=', 4)->count();
        $detratores = $pesquisas->where('nota', '<=', 2)->count();
        $nps = round((($promotores - $detratores) / $total) * 100, 1);
        return ['total' => $total, 'nps' => $nps, 'promotores' => $promotores, 'detratores' => $detratores];
    }
}
