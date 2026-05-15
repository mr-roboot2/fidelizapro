<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Plano;
use App\Services\PlanoLimiteService;
use App\Services\Pix\PixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MeuPlanoController extends Controller
{
    public function index(PlanoLimiteService $planos)
    {
        $empresa = Auth::user()->empresa->loadMissing(['plano', 'assinatura.plano']);
        $consumo = $planos->consumo($empresa);
        $planosDisponiveis = Plano::where('ativo', true)
            ->orderBy('ordem')->orderBy('preco_mensal')->get();

        $assinatura = $empresa->assinatura;
        $cobrancas = $assinatura
            ? $assinatura->cobrancas()->orderByDesc('vencimento')->limit(10)->get()
            : collect();

        return view('admin.meu_plano.index', compact(
            'empresa', 'consumo', 'planosDisponiveis', 'assinatura', 'cobrancas'
        ));
    }

    /**
     * Inicia mudança de plano: cria/atualiza Assinatura e gera Cobranca pendente.
     * Por enquanto sem gateway — a cobrança fica como 'pendente' até o admin
     * super marcar como paga. Quando integrarmos PIX (Fase 4), gera link aqui.
     */
    public function upgrade(Request $request, Plano $plano, PixService $pix)
    {
        $empresa = Auth::user()->empresa;
        if (!$plano->ativo) {
            return back()->with('error', 'Plano indisponível.');
        }

        // Bloqueio: não permite trocar de plano se houver cobrança pendente
        $assinaturaExistente = $empresa->assinatura;
        if ($assinaturaExistente) {
            $pendente = $assinaturaExistente->cobrancas()
                ->where('status', 'pendente')->first();
            if ($pendente) {
                return back()->with('error',
                    'Você tem uma cobrança pendente de R$ '
                    .number_format($pendente->valor, 2, ',', '.')
                    .' (vence '.$pendente->vencimento->format('d/m/Y')
                    .'). Pague ou cancele essa cobrança antes de trocar de plano.'
                );
            }
        }

        $cobranca = DB::transaction(function () use ($empresa, $plano) {
            $assinatura = Assinatura::firstOrNew(
                ['empresa_id' => $empresa->id],
            );

            // Snapshot do estado anterior pra permitir reverter se a cobrança
            // do upgrade for cancelada antes do pagamento.
            $snapshotUpgrade = [
                'assinatura_existia'           => $assinatura->exists,
                'plano_anterior_id'            => $assinatura->plano_id ?? $empresa->plano_id,
                'valor_mensal_anterior'        => $assinatura->valor_mensal,
                'proximo_vencimento_anterior'  => $assinatura->proximo_vencimento?->toDateString(),
                'empresa_plano_id_anterior'    => $empresa->plano_id,
                'plano_alvo_id'                => $plano->id,
            ];

            $assinatura->fill([
                'plano_id'           => $plano->id,
                'status'             => $assinatura->exists ? $assinatura->status : 'ativa',
                'gateway'            => \App\Models\ConfiguracaoSistema::instancia()->pix_provider ?: 'mock',
                'valor_mensal'       => $plano->preco_mensal,
                'inicio'             => $assinatura->exists ? $assinatura->inicio : now(),
                'proximo_vencimento' => now()->addDays(7),
            ])->save();

            $cobranca = Cobranca::create([
                'assinatura_id' => $assinatura->id,
                'empresa_id'    => $empresa->id,
                'valor'         => $plano->preco_mensal,
                'vencimento'    => now()->addDays(7),
                'status'        => 'pendente',
                'meta'          => ['upgrade' => $snapshotUpgrade],
            ]);

            $empresa->update(['plano_id' => $plano->id]);
            return $cobranca;
        });

        // Gera PIX fora da transação (chamada HTTP externa)
        $pix->gerarParaCobranca($cobranca, $empresa);

        return redirect()->route('admin.meu-plano.index')
            ->with('success', "Plano {$plano->nome} ativado! Pague o PIX abaixo pra liberar.");
    }

    /**
     * Permite que o próprio lojista cancele uma cobrança pendente da
     * assinatura dele. Necessário pra destravar troca de plano se o lojista
     * gerou uma cobrança por engano. Tenta cancelar no gateway também.
     */
    public function cancelarCobranca(Cobranca $cobranca)
    {
        $empresa = Auth::user()->empresa;
        abort_unless($cobranca->empresa_id === $empresa->id, 403);

        if ($cobranca->status === 'pago') {
            return back()->with('error', 'Não dá pra cancelar uma cobrança já paga.');
        }
        if ($cobranca->status === 'cancelado') {
            return back()->with('error', 'Cobrança já está cancelada.');
        }

        if ($cobranca->gateway_charge_id) {
            try {
                (new \App\Services\Pagamento\AsaasGateway())->cancelarCobranca($cobranca);
            } catch (\Throwable $e) {
                // segue mesmo se falhar no gateway
            }
        }
        $cobranca->update(['status' => 'cancelado']);

        $reverteu = (new \App\Services\ReverterUpgradePlano())->executar($cobranca);

        return back()->with('success', $reverteu
            ? 'Cobrança cancelada e plano revertido pro anterior.'
            : 'Cobrança cancelada. Agora você pode trocar de plano.'
        );
    }
}
