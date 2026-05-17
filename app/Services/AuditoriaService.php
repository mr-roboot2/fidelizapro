<?php

namespace App\Services;

use App\Models\AuditoriaLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditoriaService
{
    /**
     * Campos que NUNCA são gravados em auditoria_logs (antes/depois).
     * Senha hash bcrypt é offline-bruteforceable; tokens sensíveis e
     * remember_token também não devem persistir em log auditável.
     */
    protected const CAMPOS_SENSIVEIS = [
        'password', 'remember_token',
        'pdv_secret',
        'pix_api_key', 'pix_webhook_token', 'asaas_webhook_token',
        'whatsapp_api_token', 'whatsapp_client_token',
        'whatsapp_webhook_verify_token', 'whatsapp_app_secret',
        'captcha_secret_key',
        // Endereçamento de contas em gateways externos: não são segredos
        // mas dão a um leaker o link direto pra contas Asaas/Meta. Redact
        // por princípio de menor exposição.
        'whatsapp_phone_id', 'whatsapp_waba_id',
        'gateway_subscription_id', 'gateway_customer_id',
    ];

    public function registrar(
        string $acao,
        ?object $entidade = null,
        ?array $antes = null,
        ?array $depois = null,
        ?string $descricao = null,
        ?int $empresaIdOverride = null
    ): AuditoriaLog {
        // Tenta guard 'web' primeiro (painel admin), depois Sanctum (API).
        // Sem o fallback, chamadas via API que tocam models auditados gravam
        // user_id=null — log órfão sem responsável.
        $user = Auth::guard('web')->user();
        if (!$user) {
            $sanctumUser = Auth::guard('sanctum')->user();
            if ($sanctumUser instanceof User) {
                $user = $sanctumUser;
            }
        }

        return AuditoriaLog::create([
            'user_id' => $user?->id,
            'empresa_id' => $empresaIdOverride
                ?? $user?->empresa_id
                ?? ($entidade?->empresa_id ?? null),
            'acao' => $acao,
            'entidade' => $entidade ? get_class($entidade) : null,
            'entidade_id' => $entidade?->id,
            'antes' => $this->redactSensiveis($antes),
            'depois' => $this->redactSensiveis($depois),
            'descricao' => $descricao,
            'ip' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    /**
     * Chaves nested (em arrays JSON) que carregam segredo equivalente
     * a credencial — ex: `meta.pix_copia_cola` é o BR Code que paga PIX,
     * `meta.pix_qr_code` é o PNG base64 que renderiza pagamento.
     * Pra Cobranca.meta e outros JSON auditados.
     */
    protected const CHAVES_SENSIVEIS_NESTED = [
        'pix_copia_cola', 'pix_qr_code', 'pix_qr_code_svg',
        'token', 'secret', 'api_key', 'api_token',
    ];

    /**
     * Remove campos sensíveis de arrays antes/depois antes de persistir.
     * Substitui valor por '[REDACTED]' (em vez de remover a chave) pra
     * deixar visível que o campo MUDOU sem expor o valor.
     *
     * Recursivo: cobre JSON aninhado (Cobranca.meta com BR Code, etc.).
     */
    protected function redactSensiveis(?array $dados): ?array
    {
        if ($dados === null) return null;
        $resultado = [];
        foreach ($dados as $k => $v) {
            if (in_array($k, self::CAMPOS_SENSIVEIS, true)
                || in_array($k, self::CHAVES_SENSIVEIS_NESTED, true)) {
                $resultado[$k] = '[REDACTED]';
            } elseif (is_array($v)) {
                $resultado[$k] = $this->redactSensiveis($v);
            } else {
                $resultado[$k] = $v;
            }
        }
        return $resultado;
    }
}
