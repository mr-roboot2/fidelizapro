<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            $fail('O CPF informado é inválido.');
            return;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $d = ((10 * $soma) % 11) % 10;
            if ($d !== (int) $cpf[$t]) {
                $fail('O CPF informado é inválido.');
                return;
            }
        }
    }
}
