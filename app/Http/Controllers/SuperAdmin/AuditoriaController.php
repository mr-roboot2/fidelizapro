<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaLog;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditoriaLog::with(['user', 'empresa']);

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
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $acoes = AuditoriaLog::distinct()->orderBy('acao')->pluck('acao');

        return view('super.auditoria.index', compact('logs', 'empresas', 'users', 'acoes'));
    }

    public function show(AuditoriaLog $log)
    {
        $log->load('user', 'empresa');
        return view('super.auditoria.show', compact('log'));
    }
}
