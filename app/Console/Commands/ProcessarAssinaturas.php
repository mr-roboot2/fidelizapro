<?php

namespace App\Console\Commands;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Services\Pix\PixService;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Throwable;

class ProcessarAssinaturas extends Command
{
    protected $signature = 'assinaturas:processar';
    protected $description = 'Cron diário: gera próxima cobrança, marca inadimplente, notifica via WhatsApp';

    public function handle(PixService $pix, WhatsappService $whatsapp): int
    {
        $this->info('Processando assinaturas...');

        $gerados      = $this->gerarProximasCobrancas($pix);
        $marcadas     = $this->marcarInadimplentes();
        $notifProx    = $this->notificarProximas($whatsapp);
        $notifVencida = $this->notificarVencidas($whatsapp);

        $this->info("Cobranças geradas: {$gerados}");
        $this->info("Inadimplentes marcadas: {$marcadas}");
        $this->info("Notificações de próximo vencimento: {$notifProx}");
        $this->info("Notificações de vencidas: {$notifVencida}");
        return self::SUCCESS;
    }

    /**
     * Pra cada assinatura ativa cujo próximo_vencimento cai nos próximos 5 dias,
     * cria Cobranca pendente se ainda não existe pra esse vencimento.
     */
    private function gerarProximasCobrancas(PixService $pix): int
    {
        $hoje = now()->toDateString();
        $limite = now()->addDays(5)->toDateString();
        $n = 0;

        $assinaturas = Assinatura::with('empresa', 'plano')
            ->whereIn('status', ['ativa', 'trial'])
            ->whereBetween('proximo_vencimento', [$hoje, $limite])
            ->get();

        foreach ($assinaturas as $a) {
            $jaTem = Cobranca::where('assinatura_id', $a->id)
                ->where('vencimento', $a->proximo_vencimento)
                ->where('status', '!=', 'cancelado')
                ->exists();
            if ($jaTem) continue;

            try {
                $cobranca = Cobranca::create([
                    'assinatura_id' => $a->id,
                    'empresa_id'    => $a->empresa_id,
                    'valor'         => $a->valor_mensal,
                    'vencimento'    => $a->proximo_vencimento,
                    'status'        => 'pendente',
                ]);
                $pix->gerarParaCobranca($cobranca, $a->empresa);
                $n++;
            } catch (Throwable $e) {
                report($e);
                $this->error("  erro empresa #{$a->empresa_id}: {$e->getMessage()}");
            }
        }
        return $n;
    }

    /**
     * Assinaturas ativas com proximo_vencimento < hoje viram inadimplente.
     */
    private function marcarInadimplentes(): int
    {
        return Assinatura::where('status', 'ativa')
            ->whereDate('proximo_vencimento', '<', now()->toDateString())
            ->update(['status' => 'inadimplente']);
    }

    /**
     * Cobranças pendentes vencendo em 3 dias OU 1 dia OU hoje → notifica.
     * Idempotente por dia via cobranca.meta.notif_prox_{N}d.
     */
    private function notificarProximas(WhatsappService $whatsapp): int
    {
        $alvos = [3, 1, 0];
        $hoje = now()->startOfDay();
        $n = 0;

        foreach ($alvos as $dias) {
            $data = $hoje->copy()->addDays($dias)->toDateString();
            $cobrancas = Cobranca::with('empresa')
                ->where('status', 'pendente')
                ->whereDate('vencimento', $data)
                ->get();

            foreach ($cobrancas as $c) {
                $empresa = $c->empresa;
                if (!$empresa?->telefone) continue;

                $meta = $c->meta ?? [];
                $marca = "notif_prox_{$dias}d";
                if (!empty($meta[$marca])) continue;

                try {
                    $whatsapp->enviarEvento(
                        $empresa, $empresa->telefone, 'cobranca_vence_em_breve',
                        [$empresa->nome, number_format($c->valor, 2, ',', '.'), (string) $dias],
                        origem: 'cobranca'
                    );
                    $meta[$marca] = now()->toDateTimeString();
                    $c->update(['meta' => $meta]);
                    $n++;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }
        return $n;
    }

    /**
     * Cobranças que venceram há 1, 7, 15 e 30 dias → reminder.
     */
    private function notificarVencidas(WhatsappService $whatsapp): int
    {
        $alvos = [1, 7, 15, 30];
        $hoje = now()->startOfDay();
        $n = 0;

        foreach ($alvos as $dias) {
            $data = $hoje->copy()->subDays($dias)->toDateString();
            $cobrancas = Cobranca::with('empresa')
                ->where('status', 'pendente')
                ->whereDate('vencimento', $data)
                ->get();

            foreach ($cobrancas as $c) {
                $empresa = $c->empresa;
                if (!$empresa?->telefone) continue;

                $meta = $c->meta ?? [];
                $marca = "notif_venc_{$dias}d";
                if (!empty($meta[$marca])) continue;

                try {
                    $whatsapp->enviarEvento(
                        $empresa, $empresa->telefone, 'cobranca_vencida',
                        [$empresa->nome, number_format($c->valor, 2, ',', '.'), (string) $dias],
                        origem: 'cobranca'
                    );
                    $meta[$marca] = now()->toDateTimeString();
                    $c->update(['meta' => $meta]);
                    $n++;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }
        return $n;
    }
}
