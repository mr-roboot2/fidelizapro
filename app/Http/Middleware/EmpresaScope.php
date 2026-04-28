<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaScope
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $request->attributes->set('empresa_id', $user->empresa_id);
            view()->share('empresaAtiva', $user->empresa);
            view()->share('userAtivo', $user);
        }
        return $next($request);
    }
}
