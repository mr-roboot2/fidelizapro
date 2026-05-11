<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Horários configuráveis em /super/configuracoes — fallback hardcoded
// caso a tabela ainda não exista (durante o instalador) ou o cache esteja vazio.
$horarioCashback   = '03:00';
$horarioAutomacoes = '09:00';

try {
    if (\Illuminate\Support\Facades\Schema::hasTable('configuracoes_sistema')) {
        $config = \App\Models\ConfiguracaoSistema::instancia();
        $horarioCashback   = $config->horario_cashback   ?: $horarioCashback;
        $horarioAutomacoes = $config->horario_automacoes ?: $horarioAutomacoes;
    }
} catch (\Throwable $e) {
    // banco indisponível durante artisan list/install
}

Schedule::command('cashback:liberar')->dailyAt($horarioCashback);
Schedule::command('automacoes:executar')->dailyAt($horarioAutomacoes);
Schedule::command('roleta:processar-gatilhos')->dailyAt('06:00');
Schedule::command('assinaturas:processar')->dailyAt('07:00');
