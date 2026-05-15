<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Loga como o admin de uma empresa, salvando o ID original em sessão.
     */
    public function entrar(Request $request, Empresa $empresa)
    {
        $admin = User::where('empresa_id', $empresa->id)
            ->where('role', 'admin')
            ->where('ativo', true)
            ->first();

        if (!$admin) {
            return back()->with('error', 'Esta empresa não tem admin ativo. Crie um primeiro.');
        }

        $request->session()->put('impersonate_origem_id', Auth::id());
        Auth::loginUsingId($admin->id);
        // Regenera ID de sessão para evitar fixation antes da troca de identidade
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('success', "Logado como {$admin->name} ({$empresa->nome}). Use 'Voltar ao super admin' para sair.");
    }

    /**
     * Volta ao usuário super admin original.
     */
    public function sair(Request $request)
    {
        $origemId = $request->session()->pull('impersonate_origem_id');
        if (!$origemId) {
            return redirect()->route('super.dashboard');
        }
        Auth::loginUsingId($origemId);
        $request->session()->regenerate();
        return redirect()->route('super.dashboard')->with('success', 'Voltou ao super admin.');
    }
}
