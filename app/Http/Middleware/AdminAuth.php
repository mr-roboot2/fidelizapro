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

        // Super admin sem empresa vinculada vai pra área dele
        $user = Auth::user();
        if ($user->isSuperAdmin() && empty($user->empresa_id)) {
            return redirect()->route('super.dashboard');
        }

        return $next($request);
    }
}
