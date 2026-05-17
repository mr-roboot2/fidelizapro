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

// withoutOverlapping() evita 2 instâncias do mesmo cron rodando ao mesmo
// tempo (cron antigo atrasado encontra o novo do dia seguinte). Sem isso,
// assinaturas:processar (que faz HTTP calls pro gateway PIX e pode
// estender) chocava com a próxima execução: UPDATEs concorrentes em
// `cobrancas.meta` perdiam updates (read-modify-write sem lock JSON), e
// notificações WhatsApp disparavam em duplicata pra mesma cobrança.
// O lock expira em 24h por default (suficiente pra todos os jobs).
Schedule::command('cashback:liberar')->dailyAt($horarioCashback)->withoutOverlapping();
Schedule::command('automacoes:executar')->dailyAt($horarioAutomacoes)->withoutOverlapping();
Schedule::command('roleta:processar-gatilhos')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('assinaturas:processar')->dailyAt('07:00')->withoutOverlapping();
// Cleanup diário às 02h (horário de menor uso) — purga otp_codigos,
// whatsapp_envios, auditoria_logs, cron_execucoes e automacao_logs
// pra evitar tabelas crescendo indefinidamente.
Schedule::command('limpar:logs-antigos')->dailyAt('02:00')->withoutOverlapping();
