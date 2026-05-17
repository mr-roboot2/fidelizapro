<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function __construct(protected AuditoriaService $auditoria) {}

    /**
     * Loga como o admin de uma empresa, salvando o ID original em sessão.
     */
    public function entrar(Request $request, Empresa $empresa)
    {
        // Bloqueia re-impersonate: super entra em A, clica impersonate de B
        // sem sair → Auth::id() é o admin A. session()->put sobrescreve
        // impersonate_origem_id com admin_a, perdendo o super original. No
        // sair, valida que origemId é super (admin_a não é) → logout
        // total, super preso na tela de login.
        if ($request->session()->has('impersonate_origem_id')) {
            return back()->with('error',
                'Você já está impersonando outra empresa. Saia primeiro pra entrar em outra.');
        }

        $admin = User::where('empresa_id', $empresa->id)
            ->where('role', 'admin')
            ->where('ativo', true)
            ->first();

        if (!$admin) {
            return back()->with('error', 'Esta empresa não tem admin ativo. Crie um primeiro.');
        }

        $origem = Auth::user();

        // Auditoria: super admin entrando como admin de empresa. Registra
        // ANTES da troca de Auth pra capturar quem realmente clicou.
        $this->auditoria->registrar(
            'impersonate.entrar',
            $admin,
            null,
            null,
            "Super admin '{$origem?->name}' (id={$origem?->id}) entrou como '{$admin->name}' em '{$empresa->nome}' (empresa_id={$empresa->id})",
            $empresa->id
        );

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

        // Defesa em profundidade: o $origemId vem da sessão, que normalmente
        // foi setada por entrar() — onde o user JÁ era super_admin. Mas se
        // a sessão for forjada/comprometida com um id arbitrário, validar
        // antes do loginUsingId impede privilege escalation pra qualquer
        // conta da base.
        $origem = User::find($origemId);
        if (!$origem || !$origem->isSuperAdmin() || !$origem->ativo) {
            // Limpa qualquer estado residual e devolve pro login.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->with('error', 'Sessão de impersonação inválida. Faça login novamente.');
        }

        $impersonado = Auth::user();
        Auth::loginUsingId($origem->id);
        $request->session()->regenerate();

        $this->auditoria->registrar(
            'impersonate.sair',
            $origem,
            null,
            null,
            "Super admin '{$origem->name}' (id={$origem->id}) saiu da impersonação de '{$impersonado?->name}' (id={$impersonado?->id})"
        );

        return redirect()->route('super.dashboard')->with('success', 'Voltou ao super admin.');
    }
}
