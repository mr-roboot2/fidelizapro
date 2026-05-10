<?php

namespace App\Console\Commands;

use App\Models\Roleta;
use App\Services\ProcessarGatilhosRoletaService;
use Illuminate\Console\Command;
use Throwable;

class ProcessarGatilhosRoleta extends Command
{
    protected $signature = 'roleta:processar-gatilhos';
    protected $description = 'Processa os gatilhos automáticos das roletas (aniversário, inativos, etc) e credita giros';

    public function handle(ProcessarGatilhosRoletaService $service): int
    {
        $this->info('Processando gatilhos da roleta...');

        $roletas = Roleta::where('ativa', true)->with('gatilhos')->get();
        $totalDisparos = 0;

        foreach ($roletas as $roleta) {
            try {
                $resumo = $service->processar($roleta);
                $disparos = array_sum($resumo);
                $totalDisparos += $disparos;
                if ($disparos > 0) {
                    $this->line("  empresa #{$roleta->empresa_id}: ".$this->resumoLinha($resumo));
                }
            } catch (Throwable $e) {
                $this->error("  empresa #{$roleta->empresa_id}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Total de giros creditados: {$totalDisparos}");
        return self::SUCCESS;
    }

    private function resumoLinha(array $resumo): string
    {
        return collect($resumo)
            ->filter(fn ($n) => $n > 0)
            ->map(fn ($n, $tipo) => "{$tipo}={$n}")
            ->implode(' ');
    }
}
