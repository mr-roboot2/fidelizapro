<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validação de captcha em endpoints públicos (login/cadastro/OTP).
 *
 * Provider configurado via env CAPTCHA_PROVIDER:
 *   - `disabled` (default): pula validação. Use durante setup ou em ambientes
 *     onde captcha não faz sentido (CI, dev local).
 *   - `turnstile`: Cloudflare Turnstile (gratuito, sem fricção visual).
 *     Requer TURNSTILE_SITE_KEY (frontend) + TURNSTILE_SECRET_KEY (backend).
 *
 * Como ligar Turnstile:
 *   1. Criar widget em https://dash.cloudflare.com/?to=/:account/turnstile
 *   2. Copiar Site Key e Secret Key
 *   3. No .env: CAPTCHA_PROVIDER=turnstile, TURNSTILE_SITE_KEY=..., TURNSTILE_SECRET_KEY=...
 *   4. Limpar cache: `php artisan config:clear`
 *
 * O frontend (Blade) inclui o widget condicionalmente quando provider
 * estiver ligado. O backend valida via middleware `captcha`.
 */
class CaptchaService
{
    public function isEnabled(): bool
    {
        return $this->provider() !== 'disabled' && !empty($this->siteKey()) && !empty($this->secretKey());
    }

    public function provider(): string
    {
        return (string) env('CAPTCHA_PROVIDER', 'disabled');
    }

    public function siteKey(): ?string
    {
        return env('TURNSTILE_SITE_KEY');
    }

    public function secretKey(): ?string
    {
        return env('TURNSTILE_SECRET_KEY');
    }

    /**
     * Valida o token de captcha do request. Retorna true se passou ou se
     * captcha está desligado. Falha de rede/timeout = bloqueia (fail-closed
     * em produção). Token vazio = bloqueia.
     */
    public function validar(string $token, ?string $ip = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $provider = $this->provider();
        if ($provider !== 'turnstile') {
            Log::warning("[Captcha] Provider desconhecido: {$provider}");
            return false;
        }

        try {
            $resp = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => $this->secretKey(),
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            if (!$resp->ok()) {
                Log::warning('[Captcha] Resposta HTTP não-OK do Turnstile', ['status' => $resp->status()]);
                return false;
            }

            $body = $resp->json();
            $sucesso = (bool) ($body['success'] ?? false);

            if (!$sucesso) {
                Log::info('[Captcha] Token rejeitado', [
                    'codes' => $body['error-codes'] ?? [],
                    'ip'    => $ip,
                ]);
            }

            return $sucesso;
        } catch (\Throwable $e) {
            Log::warning('[Captcha] Falha validando Turnstile: '.$e->getMessage());
            return false;
        }
    }
}
