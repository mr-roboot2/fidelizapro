<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        // Super admin desativado por outro super admin (off-boarding, conta
        // comprometida) continuava entrando até sessão expirar. AdminAuth
        // já cobre isso pra admin/gerente/atendente; aqui é o gêmeo pra super.
        if (!$user->ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Sua conta foi desativada.']);
        }

        if (!$user->isSuperAdmin()) {
            abort(403, 'Acesso restrito ao Super Admin.');
        }

        return $next($request);
    }
}
