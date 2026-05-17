<?php

/**
 * Configurações de serviços externos. Lê do .env via env() AQUI dentro de
 * config/ pra que `php artisan config:cache` funcione corretamente —
 * env() em runtime fora de config/ retorna null quando o cache está
 * ativo (php-fpm não tem $_ENV populado).
 *
 * Acessar via:
 *   config('services.asaas.api_key')
 *   config('services.captcha.provider')
 */
return [

    'asaas' => [
        'env'     => env('ASAAS_ENV', 'sandbox'),
        'api_key' => env('ASAAS_API_KEY'),
    ],

    'captcha' => [
        'provider'   => env('CAPTCHA_PROVIDER', 'disabled'),
        'site_key'   => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
];
