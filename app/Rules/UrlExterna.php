<?php

namespace App\Rules;

use App\Support\UrlGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rule de validação: a URL precisa apontar pra um host EXTERNO público.
 * Bloqueia loopback, IPs privados, metadata cloud (169.254.169.254),
 * schemes não-http/https e hostnames .local/.internal.
 *
 * Combine com `nullable|url` para deixar `url` checar formato e esta rule
 * checar segurança:
 *
 *   'whatsapp_api_url' => ['nullable', 'url', new UrlExterna()]
 */
class UrlExterna implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) return;

        if (UrlGuard::isInternal((string) $value)) {
            $fail('A URL aponta para um endereço interno/loopback e não pode ser usada.');
        }
    }
}
