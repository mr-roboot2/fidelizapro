<?php

namespace App\Services;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Validação de captcha em endpoints públicos (login/cadastro/OTP).
 *
 * Provider configurável em DOIS lugares (DB tem preferência):
 *   1. /super/configuracoes (campo captcha_provider/site/secret) —
 *      preferencial. Super admin liga/desliga sem precisar mexer no env.
 *   2. .env (CAPTCHA_PROVIDER, TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY) —
 *      fallback. Útil em CI/dev/staging onde quer comportamento fixo.
 *
 * Valores possíveis de provider:
 *   - `disabled` (default): pula validação. Middleware vira no-op.
 *   - `turnstile`: Cloudflare Turnstile (gratuito, sem fricção visual).
 *
 * Como ligar Turnstile via super admin:
 *   1. https://dash.cloudflare.com → Turnstile → Add site
 *   2. Copiar Site Key + Secret Key
 *   3. /super/configuracoes → seção Captcha → preencher e salvar
 *
 * O frontend (Blade) inclui o widget condicionalmente quando
 * isEnabled() retorna true. O backend valida via middleware `captcha`.
 *
 * Validação é fail-closed: timeout/erro de rede = request rejeitado.
 */
class CaptchaService
{
    public function isEnabled(): bool
    {
        return $this->provider() !== 'disabled'
            && !empty($this->siteKey())
            && !empty($this->secretKey());
    }

    public function provider(): string
    {
        $db = $this->config('captcha_provider');
        if (!empty($db)) return $db;
        return (string) env('CAPTCHA_PROVIDER', 'disabled');
    }

    public function siteKey(): ?string
    {
        $db = $this->config('captcha_site_key');
        if (!empty($db)) return $db;
        return env('TURNSTILE_SITE_KEY');
    }

    public function secretKey(): ?string
    {
        $db = $this->config('captcha_secret_key');
        if (!empty($db)) return $db;
        return env('TURNSTILE_SECRET_KEY');
    }

    /**
     * Lê config do banco com guard pra primeira instalação (tabela
     * ainda inexistente, ou DB indisponível). Retorna null se algo falhar.
     */
    protected function config(string $key): ?string
    {
        try {
            $valor = ConfiguracaoSistema::instancia()->{$key} ?? null;
            return is_string($valor) && $valor !== '' ? $valor : null;
        } catch (Throwable $e) {
            return null;
        }
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
        } catch (Throwable $e) {
            Log::warning('[Captcha] Falha validando Turnstile: '.$e->getMessage());
            return false;
        }
    }
}
