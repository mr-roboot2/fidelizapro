<?php

namespace App\Services;

use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Empresa;
use App\Models\Plano;
use App\Services\Pagamento\AsaasGateway;
use App\Services\Pagamento\GatewayInterface;
use App\Services\Pagamento\MockGateway;
use Illuminate\Support\Facades\DB;

class AssinaturaService
{
    public function gateway(string $nome): GatewayInterface
    {
        return match ($nome) {
            'asaas' => new AsaasGateway(),
            default => new MockGateway(),
        };
    }

    /**
     * Cria assinatura para uma empresa em um plano.
     * Pode iniciar com trial (default 7 dias) ou ativar imediato.
     */
    public function criar(Empresa $empresa, Plano $plano, string $gateway = 'mock', int $diasTrial = 7): Assinatura
    {
        return DB::transaction(function () use ($empresa, $plano, $gateway, $diasTrial) {
            $driver = $this->gateway($gateway);

            $customerId = $driver->criarCustomer($empresa);

            $assinatura = Assinatura::create([
                'empresa_id' => $empresa->id,
                'plano_id' => $plano->id,
                'status' => $diasTrial > 0 ? 'trial' : 'ativa',
                'gateway' => $gateway,
                'gateway_customer_id' => $customerId,
                'valor_mensal' => $plano->preco_mensal,
                'inicio' => now(),
                'trial_ate' => $diasTrial > 0 ? now()->addDays($diasTrial) : null,
                'proximo_vencimento' => now()->addDays(max($diasTrial, 1)),
            ]);

            $subscriptionId = $driver->criarAssinatura($assinatura);
            $assinatura->update(['gateway_subscription_id' => $subscriptionId]);

            // Atualiza empresa com plano
            $empresa->update(['plano_id' => $plano->id]);

            return $assinatura;
        });
    }

    /**
     * Gera próxima cobrança (rodado via cron mensalmente).
     */
    public function gerarProximaCobranca(Assinatura $assinatura): Cobranca
    {
        $driver = $this->gateway($assinatura->gateway);

        $cobranca = Cobranca::create([
            'assinatura_id' => $assinatura->id,
            'empresa_id' => $assinatura->empresa_id,
            'valor' => $assinatura->valor_mensal,
            'vencimento' => $assinatura->proximo_vencimento ?? now()->addDays(7),
            'status' => 'pendente',
        ]);

        $driver->gerarCobranca($cobranca);

        return $cobranca->fresh();
    }

    /**
     * Confirma pagamento (chamado pelo webhook ou pelo super-admin no botão
     * 'Marcar paga'). Se a cobrança for de upgrade de plano, efetiva.
     *
     * Race fix: dois webhooks chegando em paralelo (Asaas faz retry agressivo)
     * podiam aplicar `AplicarUpgradePlano` duas vezes, porque ambos leem
     * `status='pendente'` antes de qualquer um atualizar. Tudo agora roda
     * dentro de DB::transaction com lockForUpdate, garantindo que o segundo
     * caia no early-return `status === 'pago'`.
     */
    public function marcarPaga(Cobranca $cobranca, ?string $gatewayChargeId = null): void
    {
        DB::transaction(function () use ($cobranca, $gatewayChargeId) {
            $lockada = Cobranca::lockForUpdate()->find($cobranca->id);
            // Só transiciona de 'pendente' pra 'pago'. Bug anterior: o early-
            // return testava só 'pago', então cobrança em 'cancelado',
            // 'estornado' ou 'vencido' virava 'pago' e disparava
            // AplicarUpgradePlano — super-admin clicava "Marcar paga" em
            // cobrança cancelada e a empresa ganhava upgrade sem ter pago.
            if (!$lockada || $lockada->status !== 'pendente') {
                return;
            }

            $lockada->update([
                'status' => 'pago',
                'pago_em' => now(),
                'gateway_charge_id' => $gatewayChargeId ?? $lockada->gateway_charge_id,
            ]);

            (new AplicarUpgradePlano())->executar($lockada->fresh());

            // Lock + monotonicidade do proximo_vencimento. Sem o lock na
            // Assinatura, 2 webhooks de cobranças DIFERENTES da mesma
            // assinatura avançam só 1 mês ao invés de 2 (último write
            // vence). E sem o max(), um webhook que chega atrasado pode
            // RETROAGIR o vencimento (cobrança de janeiro paga em março
            // → proximo_vencimento volta pra fevereiro). max() preserva
            // monotonicidade.
            $assinatura = Assinatura::lockForUpdate()->find($lockada->assinatura_id);
            if ($assinatura) {
                $candidato = $lockada->vencimento->copy()->addMonth();
                $atual = $assinatura->proximo_vencimento;
                $novoVencimento = ($atual && $atual->gt($candidato)) ? $atual : $candidato;
                $assinatura->update([
                    'status' => 'ativa',
                    'proximo_vencimento' => $novoVencimento,
                ]);
            }
        });
    }

    /**
     * Cancela a assinatura no gateway externo + grava status local. O
     * cancelamento no gateway ACONTECE antes do DB update mas a ordem é
     * deliberada: marcar 'cancelada' localmente antes de confirmar com o
     * gateway pode deixar empresa sem acesso enquanto o gateway ainda
     * cobra. Pra fechar a janela de inconsistência (gateway cancelou mas DB
     * crashou no update), usamos DB::transaction + lockForUpdate — outro
     * processo concorrente não pode mexer na linha enquanto a transação
     * roda. Se o driver lança (RuntimeException), nada é gravado e o
     * super-admin retenta. Se o DB falha após o gateway confirmar, o retry
     * é idempotente pq o cancelar do Asaas tolera "já cancelada".
     */
    public function cancelar(Assinatura $assinatura): void
    {
        DB::transaction(function () use ($assinatura) {
            $lockada = Assinatura::lockForUpdate()->find($assinatura->id);
            if (!$lockada || $lockada->status === 'cancelada') {
                return;
            }

            $driver = $this->gateway($lockada->gateway);
            $driver->cancelarAssinatura($lockada);

            // Cancela cobranças pendentes (incluindo upgrades pendentes) e
            // zera plano_id_pendente. Sem isso, cliente que paga uma
            // cobrança antiga depois do cancelamento reativava a assinatura
            // silenciosamente (AplicarUpgradePlano setava status='ativa') e
            // ganhava o plano alvo "de graça" pq AssinaturaService::marcarPaga
            // não filtrava assinatura cancelada.
            Cobranca::where('assinatura_id', $lockada->id)
                ->where('status', 'pendente')
                ->update(['status' => 'cancelado']);

            $lockada->update([
                'status'            => 'cancelada',
                'cancelada_em'      => now(),
                'plano_id_pendente' => null,
            ]);
        });
    }

    /**
     * Marca assinaturas vencidas como inadimplentes (cron diário).
     */
    public function marcarVencidas(): int
    {
        $count = 0;
        Assinatura::where('status', 'ativa')
            ->whereDate('proximo_vencimento', '<', now())
            ->each(function (Assinatura $a) use (&$count) {
                // Verifica se há cobranças pendentes vencidas
                $temPendente = Cobranca::where('assinatura_id', $a->id)
                    ->where('status', 'pendente')
                    ->whereDate('vencimento', '<', now())
                    ->exists();
                if ($temPendente) {
                    $a->update(['status' => 'inadimplente']);
                    $count++;
                }
            });
        return $count;
    }
}
