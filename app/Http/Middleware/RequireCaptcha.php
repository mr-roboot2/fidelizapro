<?php

namespace App\Http\Middleware;

use App\Services\CaptchaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Bloqueia request se captcha falhar ou estiver ausente.
 * Quando CAPTCHA_PROVIDER=disabled, passa direto (no-op).
 *
 * Aceita o token via:
 *   - body `cf-turnstile-response` (campo padrão do widget Turnstile)
 *   - body `captcha_token` (fallback genérico)
 *   - header `X-Captcha-Token` (pra APIs JSON)
 */
class RequireCaptcha
{
    public function __construct(protected CaptchaService $captcha) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->captcha->isEnabled()) {
            return $next($request);
        }

        $token = (string) (
            $request->input('cf-turnstile-response')
            ?? $request->input('captcha_token')
            ?? $request->header('X-Captcha-Token', '')
        );

        if (!$this->captcha->validar($token, $request->ip())) {
            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'captcha' => 'Verificação anti-robô falhou. Recarregue a página e tente novamente.',
                ]);
            }
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['captcha' => 'Verificação anti-robô falhou. Recarregue a página e tente novamente.']);
        }

        return $next($request);
    }
}
