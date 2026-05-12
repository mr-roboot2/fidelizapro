<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Telefone BR — aceita 10 (fixo) ou 11 (celular) dígitos, DDD entre 11 e 99.
 * Celular precisa começar com 9 no terceiro dígito.
 */
class TelefoneBr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $d = preg_replace('/\D/', '', (string) $value);

        if (strlen($d) !== 10 && strlen($d) !== 11) {
            $fail('Telefone inválido. Informe DDD + número.');
            return;
        }

        $ddd = (int) substr($d, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            $fail('DDD inválido.');
            return;
        }

        if (strlen($d) === 11 && $d[2] !== '9') {
            $fail('Celular deve começar com 9 após o DDD.');
        }
    }
}
