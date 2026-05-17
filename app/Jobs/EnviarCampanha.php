<?php

namespace App\Jobs;

use App\Models\Campanha;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Processa o disparo de uma campanha WhatsApp em background.
 *
 * Antes do refactor, `WhatsappService::dispararCampanha` rodava
 * síncrono no request HTTP do super admin — campanha pra 1000 clientes
 * com timeout 15s por envio = até 4h num único request, estourando
 * `max_execution_time` do PHP-FPM bem antes. O super clicava
 * "disparar", request travava, ele clicava de novo, e mensagens iam
 * duplicadas.
 *
 * Configuração:
 *   - QUEUE_CONNECTION=sync (dev): roda imediato, mesmo comportamento de antes
 *   - QUEUE_CONNECTION=database (prod): entra na fila, worker processa
 *     com `php artisan queue:work` rodando como serviço
 *
 * Tries=1 (sem retry automático) — campanha disparada parcialmente +
 * retry duplica mensagens. Super dispara de novo manual se algo falhar.
 * Timeout=1800s (30min) cobre campanhas de até ~3500 mensagens.
 */
class EnviarCampanha implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(public Campanha $campanha) {}

    public function handle(WhatsappService $service): void
    {
        $service->dispararCampanhaImediato($this->campanha);
    }

    /**
     * Se o Job falhar (timeout/exception), volta status pra 'rascunho'
     * pra super tentar de novo manualmente.
     */
    public function failed(\Throwable $exception): void
    {
        try {
            $this->campanha->update(['status' => 'rascunho']);
        } catch (\Throwable $e) {
            // log only
        }
        report($exception);
    }
}
