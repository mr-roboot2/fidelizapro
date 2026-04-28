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

        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Acesso restrito ao Super Admin.');
        }

        return $next($request);
    }
}
