<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Campanha;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empresa;
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

        return view('super.dashboard', compact(
            'totalEmpresas', 'empresasAtivas', 'totalUsers', 'totalClientes',
            'totalCompras', 'faturamentoTotal', 'totalResgates', 'totalCampanhas',
            'vendasUltimos30Dias', 'rankingEmpresas'
        ));
    }
}
