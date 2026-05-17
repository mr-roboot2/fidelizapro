<?php

namespace App\Observers;

use App\Models\Assinatura;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use Throwable;

class EmpresaObserver
{
    public function created(Empresa $empresa): void
    {
        try {
            $cfg = ConfiguracaoSistema::instancia();
            if (!$cfg->plano_padrao_id) return;

            // Não cria assinatura duplicada (se super admin criou manualmente)
            if (Assinatura::where('empresa_id', $empresa->id)->exists()) return;

            $plano = \App\Models\Plano::find($cfg->plano_padrao_id);
            if (!$plano) return;

            $trialDias = (int) ($cfg->trial_dias_padrao ?? 7);
            // trial=0 significa "cobra desde o cadastro" — proximo_vencimento
            // = amanhã pra que o cron `assinaturas:gerar-cobrancas` (07h)
            // gere a 1ª cobrança no dia seguinte. Antes ficava `now()` →
            // cron do dia seguinte fazia whereDate < hoje e a assinatura
            // virava inadimplente antes mesmo do gateway ter chance de
            // gerar PIX. 1 dia de folga garante a janela do cron de
            // cobrança rodar antes.
            $proximoVencimento = $trialDias > 0
                ? now()->addDays($trialDias)
                : now()->addDay();

            Assinatura::create([
                'empresa_id'         => $empresa->id,
                'plano_id'           => $plano->id,
                'status'             => $trialDias > 0 ? 'trial' : 'ativa',
                'gateway'            => $cfg->pix_provider ?: 'mock',
                'valor_mensal'       => $plano->preco_mensal,
                'inicio'             => now(),
                'proximo_vencimento' => $proximoVencimento,
                'trial_ate'          => $trialDias > 0 ? now()->addDays($trialDias) : null,
            ]);

            $empresa->update(['plano_id' => $plano->id]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
