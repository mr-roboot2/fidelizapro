<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Resgate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtividadeSuspeitaController extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;

        // 1. Clientes com muitos resgates em 24h
        $resgatesEmRajada = Resgate::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDay())
            ->select('cliente_id', DB::raw('COUNT(*) as total'))
            ->groupBy('cliente_id')
            ->having('total', '>=', 3)
            ->with('cliente')
            ->get();

        // 2. Compras grandes (acima de 3x o ticket médio)
        $ticketMedio = Compra::where('empresa_id', $empresaId)->avg('valor') ?: 0;
        $comprasGrandes = Compra::where('empresa_id', $empresaId)
            ->where('valor', '>', max($ticketMedio * 3, 200))
            ->where('created_at', '>=', now()->subDays(7))
            ->with('cliente')
            ->latest()->take(20)->get();

        // 3. Mesmo IP com múltiplos clientes
        $ipsCompartilhados = Cliente::where('empresa_id', $empresaId)
            ->whereNotNull('ultimo_ip')
            ->select('ultimo_ip', DB::raw('COUNT(*) as total_clientes'))
            ->groupBy('ultimo_ip')
            ->having('total_clientes', '>=', 3)
            ->orderByDesc('total_clientes')
            ->get();

        // 4. Cadastros recentes em rajada do mesmo IP
        $cadastrosRajada = Cliente::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('ultimo_ip')
            ->select('ultimo_ip', DB::raw('COUNT(*) as total'))
            ->groupBy('ultimo_ip')
            ->having('total', '>=', 3)
            ->get();

        return view('admin.atividade_suspeita.index', compact(
            'resgatesEmRajada', 'comprasGrandes', 'ipsCompartilhados',
            'cadastrosRajada', 'ticketMedio'
        ));
    }
}
