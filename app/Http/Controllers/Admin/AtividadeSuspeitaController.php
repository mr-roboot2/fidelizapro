<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\ConfiguracaoSistema;
use App\Models\Resgate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtividadeSuspeitaController extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;

        // Limites configuráveis em /super/configuracoes. Antes hardcoded
        // em 3/3/3 — admin sem acesso a código não conseguia ajustar pra
        // empresa com perfil diferente (cliente VIP que resgata muito,
        // família compartilhando IP, etc).
        $cfg = ConfiguracaoSistema::instancia();
        $limResgates  = max(1, (int) ($cfg->antifraude_resgates_24h ?: 3));
        $limIps       = max(1, (int) ($cfg->antifraude_ips_compartilhados ?: 3));
        $limCadastros = max(1, (int) ($cfg->antifraude_cadastros_dia_ip ?: 3));

        // 1. Clientes com muitos resgates em 24h
        $resgatesEmRajada = Resgate::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDay())
            ->select('cliente_id', DB::raw('COUNT(*) as total'))
            ->groupBy('cliente_id')
            ->having('total', '>=', $limResgates)
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
            ->having('total_clientes', '>=', $limIps)
            ->orderByDesc('total_clientes')
            ->get();

        // 4. Cadastros recentes em rajada do mesmo IP
        $cadastrosRajada = Cliente::where('empresa_id', $empresaId)
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('ultimo_ip')
            ->select('ultimo_ip', DB::raw('COUNT(*) as total'))
            ->groupBy('ultimo_ip')
            ->having('total', '>=', $limCadastros)
            ->get();

        return view('admin.atividade_suspeita.index', compact(
            'resgatesEmRajada', 'comprasGrandes', 'ipsCompartilhados',
            'cadastrosRajada', 'ticketMedio',
            'limResgates', 'limIps', 'limCadastros'
        ));
    }
}
