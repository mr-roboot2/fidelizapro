<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    /**
     * Lista finita de ações conhecidas. Antes vinha de
     * AuditoriaLog::distinct()->pluck('acao') — full scan na tabela
     * (que cresce sem teto) toda vez que admin abria a página.
     */
    private const ACOES_CONHECIDAS = [
        'created', 'updated', 'deleted',
        'impersonate.entrar', 'impersonate.sair',
    ];

    public function index(Request $request)
    {
        // Default últimos 7 dias pra evitar full scan da tabela. Antes
        // não havia filtro de data — super tinha que paginar milhares
        // de logs pra achar evento recente.
        $dataDe = $request->input('data_de', now()->subDays(7)->toDateString());
        $dataAte = $request->input('data_ate', now()->toDateString());

        $query = AuditoriaLog::with(['user', 'empresa'])
            ->whereBetween('created_at', [$dataDe.' 00:00:00', $dataAte.' 23:59:59']);

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('acao')) {
            $query->where('acao', $request->input('acao'));
        }
        if ($request->filled('entidade')) {
            $query->where('entidade', 'like', '%'.$request->input('entidade').'%');
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        // Limita user list pra evitar dropdown com 10k+ rows quando a base
        // crescer. Filtro UI pode evoluir pra autocomplete depois.
        $users = User::where('ativo', true)->orderBy('name')->limit(200)->get(['id', 'name', 'email']);
        $acoes = collect(self::ACOES_CONHECIDAS);

        return view('super.auditoria.index', compact('logs', 'empresas', 'users', 'acoes', 'dataDe', 'dataAte'));
    }

    public function show(AuditoriaLog $log)
    {
        $log->load('user', 'empresa');
        return view('super.auditoria.show', compact('log'));
    }
}
