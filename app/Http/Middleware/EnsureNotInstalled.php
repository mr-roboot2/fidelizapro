<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (file_exists(storage_path('installed.lock'))) {
            return response()->view('install.locked', [], 403);
        }

        return $next($request);
    }
}
