<?php

namespace App\Console\Commands;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
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

        $regerados    = $this->regerarPixExpirados($pix);
        $gerados      = $this->gerarProximasCobrancas($pix);
        $marcadas     = $this->marcarInadimplentes();
        $notifProx    = $this->notificarProximas($whatsapp);
        $notifVencida = $this->notificarVencidas($whatsapp);

        $this->info("PIX regerados (expirados): {$regerados}");
        $this->info("Cobranças geradas: {$gerados}");
        $this->info("Inadimplentes marcadas: {$marcadas}");
        $this->info("Notificações de próximo vencimento: {$notifProx}");
        $this->info("Notificações de vencidas: {$notifVencida}");
        return self::SUCCESS;
    }

    /**
     * Cobranças pendentes cujo PIX já expirou (meta.pix_expira_em < agora)
     * ganham novo QR/copia-cola via gateway.
     */
    private function regerarPixExpirados(PixService $pix): int
    {
        $n = 0;
        $cobrancas = Cobranca::with('empresa')
            ->where('status', 'pendente')
            ->whereNotNull('meta')
            ->get();

        foreach ($cobrancas as $c) {
            try {
                $expiraEm = $c->meta['pix_expira_em'] ?? null;
                if (!$expiraEm) continue;
                // Carbon::parse num valor malformado (string vazia, "0000-00-00")
                // lançava InvalidFormatException sem catch e DERRUBAVA todo o
                // batch — 1 cobrança lixo travava centenas válidas. Try/catch
                // por iteração isola falha por cobrança.
                if (!\Carbon\Carbon::parse($expiraEm)->isPast()) continue;

                // Race em cobranca.meta: cron lê meta, edita, salva. Webhook
                // PIX concorrente fazendo o mesmo no mesmo intervalo perde
                // updates (last write wins). lockForUpdate + fresh() força
                // o cron a esperar e re-ler o estado pós-webhook.
                \Illuminate\Support\Facades\DB::transaction(function () use ($c, $pix) {
                    $lockada = Cobranca::lockForUpdate()->find($c->id);
                    // Re-check após lock: status pode ter virado 'pago' pelo
                    // webhook concorrente — nada a regerar.
                    if (!$lockada || $lockada->status !== 'pendente') return;

                    $meta = $lockada->meta ?? [];
                    unset($meta['pix_qr_code'], $meta['pix_qr_code_svg'], $meta['pix_copia_cola'], $meta['pix_expira_em']);
                    $lockada->update(['meta' => $meta, 'gateway_charge_id' => null]);
                    $pix->gerarParaCobranca($lockada->fresh(), $c->empresa);
                });
                $n++;
            } catch (Throwable $e) {
                report($e);
            }
        }
        return $n;
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
        $alvos = ConfiguracaoSistema::instancia()->avisosAntes();
        if (empty($alvos)) return 0;
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

                $marca = "notif_prox_{$dias}d";
                if (!empty(($c->meta ?? [])[$marca])) continue;

                try {
                    $whatsapp->enviarEvento(
                        $empresa, $empresa->telefone, 'cobranca_vence_em_breve',
                        [$empresa->nome, number_format($c->valor, 2, ',', '.'), (string) $dias],
                        origem: 'cobranca'
                    );
                    // Race em meta JSON: lockForUpdate re-lê meta atual
                    // (pode ter sido editado por webhook/regerarPix) antes
                    // de merge. Sem isso, read antes do envio + write
                    // depois perdia outros campos do meta.
                    \Illuminate\Support\Facades\DB::transaction(function () use ($c, $marca) {
                        $lockada = Cobranca::lockForUpdate()->find($c->id);
                        if (!$lockada) return;
                        $meta = $lockada->meta ?? [];
                        $meta[$marca] = now()->toDateTimeString();
                        $lockada->update(['meta' => $meta]);
                    });
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
        $alvos = ConfiguracaoSistema::instancia()->avisosDepois();
        if (empty($alvos)) return 0;
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

                $marca = "notif_venc_{$dias}d";
                if (!empty(($c->meta ?? [])[$marca])) continue;

                try {
                    $whatsapp->enviarEvento(
                        $empresa, $empresa->telefone, 'cobranca_vencida',
                        [$empresa->nome, number_format($c->valor, 2, ',', '.'), (string) $dias],
                        origem: 'cobranca'
                    );
                    // Race em meta JSON — ver comentário em notificarProximas.
                    \Illuminate\Support\Facades\DB::transaction(function () use ($c, $marca) {
                        $lockada = Cobranca::lockForUpdate()->find($c->id);
                        if (!$lockada) return;
                        $meta = $lockada->meta ?? [];
                        $meta[$marca] = now()->toDateTimeString();
                        $lockada->update(['meta' => $meta]);
                    });
                    $n++;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }
        return $n;
    }
}
