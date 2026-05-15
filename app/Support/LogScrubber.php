<?php

namespace App\Support;

/**
 * Mascarar PII em strings que vão pra log. Usado especialmente em response
 * body de gateways externos (Asaas, WhatsApp providers) que normalmente
 * ecoam dados da requisição (CPF/CNPJ/telefone/email do cliente) na
 * mensagem de erro.
 *
 * Não é defesa absoluta — atacante com acesso a log também pode ter
 * acesso a banco. Mas reduz exposição em cenários comuns:
 *   - Log enviado pra serviço de monitoring (Sentry, Datadog).
 *   - Log compartilhado com terceiros pra diagnóstico.
 *   - Backup de logs separado do backup do banco.
 *
 * Uso:
 *   Log::warning('[Asaas] Falha: '.LogScrubber::scrub($response->body()));
 *
 * Para arrays/objetos, use `scrubArray()`.
 */
class LogScrubber
{
    /**
     * Caracteres do truncate quando o body é muito grande (1KB default).
     */
    protected const MAX_LEN = 1024;

    /**
     * Aplica todas as máscaras + trunca.
     */
    public static function scrub(?string $texto): string
    {
        if ($texto === null || $texto === '') return '';

        // CPF (XXX.XXX.XXX-XX ou 11 dígitos seguidos)
        $texto = preg_replace('/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/', '[CPF]', $texto);
        $texto = preg_replace('/\b\d{11}\b/', '[CPF-OU-TEL]', $texto);

        // CNPJ (XX.XXX.XXX/XXXX-XX ou 14 dígitos)
        $texto = preg_replace('/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/', '[CNPJ]', $texto);
        $texto = preg_replace('/\b\d{14}\b/', '[CNPJ-OU-CC]', $texto);

        // Telefone BR ((XX) 9XXXX-XXXX, +55XXXXXXXXXXX, 9XXXX-XXXX)
        $texto = preg_replace('/\+?55\s*\(?\d{2}\)?\s*9?\d{4,5}[-\s]?\d{4}/', '[TELEFONE]', $texto);
        $texto = preg_replace('/\(\d{2}\)\s*9?\d{4,5}[-\s]?\d{4}/', '[TELEFONE]', $texto);

        // Email
        $texto = preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[EMAIL]', $texto);

        // Tokens longos (Bearer / API keys): qualquer sequência hexa/base64 ≥ 32 chars
        $texto = preg_replace('/\b[A-Za-z0-9+\/=_-]{32,}\b/', '[TOKEN]', $texto);

        // Cartão de crédito (Luhn-like — 13-19 dígitos contíguos, com ou sem espaço)
        $texto = preg_replace('/\b(?:\d[\s-]?){13,19}\b/', '[CARTAO]', $texto);

        if (strlen($texto) > self::MAX_LEN) {
            $texto = substr($texto, 0, self::MAX_LEN).'…[truncado]';
        }

        return $texto;
    }

    /**
     * Scrub recursivo em estruturas — usado pra arrays de contexto do Log.
     * Mantém chaves intactas, mascara valores string.
     */
    public static function scrubArray(array $dados): array
    {
        $resultado = [];
        foreach ($dados as $k => $v) {
            if (is_string($v)) {
                $resultado[$k] = self::scrub($v);
            } elseif (is_array($v)) {
                $resultado[$k] = self::scrubArray($v);
            } else {
                $resultado[$k] = $v;
            }
        }
        return $resultado;
    }
}
