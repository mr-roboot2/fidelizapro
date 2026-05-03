<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate limit por empresa + IP. O limite real vem da configuração da
 * empresa (campos rate_limit_auth / rate_limit_pdv). Se a empresa não
 * for resolvida no request, cai num default seguro.
 *
 * Uso: ->middleware('empresa.throttle:auth') ou 'empresa.throttle:pdv'.
 */
class EmpresaThrottle
{
    /** Defaults caso a empresa não seja resolvida do request. */
    protected const DEFAULTS = [
        'auth' => 10,
        'pdv'  => 60,
    ];

    public function handle(Request $request, Closure $next, string $tipo)
    {
        $empresa = $this->resolverEmpresa($request);
        $limit   = $this->limitePor($empresa, $tipo);

        $key = sprintf('empresa-throttle:%s:%s:%s', $tipo, $empresa?->id ?? 'global', $request->ip());

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $disponivel = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Muitas requisições. Tente novamente em {$disponivel} segundos.",
            ], 429)->header('Retry-After', $disponivel);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }

    protected function resolverEmpresa(Request $request): ?Empresa
    {
        $slug = $request->route('slug') ?? $request->input('empresa_slug');
        if (!$slug) return null;
        return Empresa::where('slug', $slug)->where('ativo', true)->first();
    }

    protected function limitePor(?Empresa $empresa, string $tipo): int
    {
        $default = self::DEFAULTS[$tipo] ?? 10;
        if (!$empresa) return $default;

        return match ($tipo) {
            'auth' => $empresa->rate_limit_auth ?: $default,
            'pdv'  => $empresa->rate_limit_pdv  ?: $default,
            default => $default,
        };
    }
}
