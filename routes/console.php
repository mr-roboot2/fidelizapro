<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera cashbacks pendentes diariamente às 03:00
Schedule::command('cashback:liberar')->dailyAt('03:00');

// Executa automações (aniversário, inativos, pontos vencendo) diariamente às 09:00
Schedule::command('automacoes:executar')->dailyAt('09:00');
