<?php

namespace App\Support;

/**
 * Normaliza telefone BR para o formato esperado pelos providers WhatsApp:
 * dígitos puros com prefixo 55 (Brasil) quando recebido sem DDI.
 *
 * Lógica antes estava triplicada (MetaCloudDriver, ZapiDriver,
 * EvolutionDriver), com risco de divergência se um lado mudasse e o
 * outro não. Helper único garante comportamento idêntico.
 *
 * Regras:
 *   - 10 dígitos (DDD + fixo 8 dígitos): prefixa 55
 *   - 11 dígitos (DDD + celular 9 + 8 dígitos): prefixa 55
 *   - 12+ dígitos: considera que já vem com DDI (ou país != BR) — devolve cru
 *   - Vazio: devolve vazio (chamador decide)
 */
class PhoneNormalizer
{
    public static function normalize(?string $telefone): string
    {
        $apenas = preg_replace('/\D/', '', (string) $telefone);
        if ($apenas === '' || $apenas === null) return '';

        $len = strlen($apenas);
        if ($len === 10 || $len === 11) {
            return '55'.$apenas;
        }
        return $apenas;
    }
}
