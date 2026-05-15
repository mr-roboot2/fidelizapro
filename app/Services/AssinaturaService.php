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
     */
    public function marcarPaga(Cobranca $cobranca, ?string $gatewayChargeId = null): void
    {
        $cobranca->update([
            'status' => 'pago',
            'pago_em' => now(),
            'gateway_charge_id' => $gatewayChargeId ?? $cobranca->gateway_charge_id,
        ]);

        (new AplicarUpgradePlano())->executar($cobranca->fresh());

        $assinatura = $cobranca->fresh()->assinatura;
        $assinatura->update([
            'status' => 'ativa',
            'proximo_vencimento' => $cobranca->vencimento->addMonth(),
        ]);
    }

    public function cancelar(Assinatura $assinatura): void
    {
        $driver = $this->gateway($assinatura->gateway);
        $driver->cancelarAssinatura($assinatura);

        $assinatura->update([
            'status' => 'cancelada',
            'cancelada_em' => now(),
        ]);
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
