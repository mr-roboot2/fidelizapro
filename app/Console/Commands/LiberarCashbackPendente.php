<?php

namespace App\Console\Commands;

use App\Services\CashbackService;
use Illuminate\Console\Command;

class LiberarCashbackPendente extends Command
{
    protected $signature = 'cashback:liberar';
    protected $description = 'Libera cashbacks pendentes que passaram do prazo de confirmação';

    public function handle(CashbackService $service): int
    {
        $count = $service->liberarPendentes();
        $this->info("Liberados {$count} movimento(s) de cashback pendentes.");
        return self::SUCCESS;
    }
}
