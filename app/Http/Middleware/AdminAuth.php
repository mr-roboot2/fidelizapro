<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        // Usuário inativado por super admin continuava entrando até sessão
        // expirar (default 120min). Agora derruba imediato.
        if (!$user->ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Sua conta foi desativada. Entre em contato com o administrador.']);
        }

        // Empresa desativada pelo super admin (toggle): operador continuava
        // operando até sessão expirar. PWA cliente já é barrado em
        // criarCliente/login porque o controller filtra `where('ativo',true)`,
        // mas operadores do painel não tinham esse check.
        if ($user->empresa_id && (!$user->empresa || !$user->empresa->ativo)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'A empresa está desativada. Entre em contato com o suporte.']);
        }

        // Super admin sem empresa vinculada vai pra área dele
        if ($user->isSuperAdmin() && empty($user->empresa_id)) {
            return redirect()->route('super.dashboard');
        }

        return $next($request);
    }
}
