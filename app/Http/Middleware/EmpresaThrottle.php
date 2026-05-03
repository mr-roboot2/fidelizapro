<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracaoSistema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Rate limit global (definido em /super/configuracoes) aplicado por IP.
 * Uso: ->middleware('empresa.throttle:auth') ou 'empresa.throttle:pdv'.
 */
class EmpresaThrottle
{
    /** Defaults caso a config global não esteja acessível ainda (ex: instalador). */
    protected const DEFAULTS = [
        'auth' => 10,
        'pdv'  => 60,
    ];

    public function handle(Request $request, Closure $next, string $tipo)
    {
        $limit = $this->limite($tipo);

        $key = sprintf('empresa-throttle:%s:%s', $tipo, $request->ip());

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $disponivel = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Muitas requisições. Tente novamente em {$disponivel} segundos.",
            ], 429)->header('Retry-After', $disponivel);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }

    protected function limite(string $tipo): int
    {
        $default = self::DEFAULTS[$tipo] ?? 10;
        try {
            $config = ConfiguracaoSistema::instancia();
            return match ($tipo) {
                'auth' => $config->rate_limit_auth ?: $default,
                'pdv'  => $config->rate_limit_pdv  ?: $default,
                default => $default,
            };
        } catch (Throwable $e) {
            return $default;
        }
    }
}
