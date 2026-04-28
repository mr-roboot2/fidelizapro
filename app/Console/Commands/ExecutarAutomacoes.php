<?php

namespace App\Console\Commands;

use App\Services\AutomacaoService;
use Illuminate\Console\Command;

class ExecutarAutomacoes extends Command
{
    protected $signature = 'automacoes:executar';
    protected $description = 'Executa todas as automações agendadas (aniversário, inativos, pontos vencendo)';

    public function handle(AutomacaoService $service): int
    {
        $this->info('Executando automações...');
        $r = $service->executarTodas();
        $this->info("Automações processadas: {$r['automacoes']}");
        $this->info("Mensagens enviadas: {$r['enviados']}");
        if ($r['falhas'] > 0) $this->warn("Falhas: {$r['falhas']}");
        return self::SUCCESS;
    }
}
