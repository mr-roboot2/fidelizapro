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
        $empresa = Auth::user()->empresa->loadMissing(['plano', 'assinatura.plano', 'assinatura.planoPendente']);
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

        // Race fix: o check `pendente !== null` ANTES da transaction
        // permitia 2 cliques quase-simultâneos criarem 2 cobranças. Agora
        // o check roda DENTRO da transaction com lockForUpdate na
        // Assinatura — segundo clique espera o lock e vê o cancelamento
        // do primeiro (se houve) ou a cobrança pendente recém-criada.
        $cobranca = null;
        $erro = null;

        DB::transaction(function () use ($empresa, $plano, &$cobranca, &$erro) {
            $assinatura = Assinatura::where('empresa_id', $empresa->id)
                ->whereNotIn('status', ['cancelada'])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($assinatura) {
                $pendente = Cobranca::where('assinatura_id', $assinatura->id)
                    ->where('status', 'pendente')
                    ->first();
                if ($pendente) {
                    $erro = 'Você tem uma cobrança pendente de R$ '
                        .number_format($pendente->valor, 2, ',', '.')
                        .' (vence '.$pendente->vencimento->format('d/m/Y')
                        .'). Pague ou cancele essa cobrança antes de trocar de plano.';
                    return;
                }
            }

            $assinatura = $assinatura ?: Assinatura::firstOrNew(
                ['empresa_id' => $empresa->id],
            );

            // Plano só efetiva quando o PIX for confirmado. Aqui apenas
            // setamos plano_id_pendente e criamos a cobrança alvo. O
            // plano_id atual (e empresa.plano_id) permanecem inalterados.
            if ($assinatura->exists) {
                $assinatura->update(['plano_id_pendente' => $plano->id]);
            } else {
                // Primeira assinatura: criamos sem plano ativo (lojista
                // não ganha acesso até pagar a primeira cobrança).
                $assinatura->fill([
                    'plano_id'           => null,
                    'plano_id_pendente'  => $plano->id,
                    'status'             => 'inadimplente',
                    'gateway'            => \App\Models\ConfiguracaoSistema::instancia()->pix_provider ?: 'mock',
                    'valor_mensal'       => 0,
                    'inicio'             => now(),
                    'proximo_vencimento' => null,
                ])->save();
            }

            $cobranca = Cobranca::create([
                'assinatura_id' => $assinatura->id,
                'empresa_id'    => $empresa->id,
                'valor'         => $plano->preco_mensal,
                'vencimento'    => now()->addDays(7),
                'status'        => 'pendente',
                'meta'          => ['upgrade' => ['plano_alvo_id' => $plano->id]],
            ]);
        });

        if ($erro) {
            return back()->with('error', $erro);
        }

        // Gera PIX fora da transação (chamada HTTP externa)
        $pix->gerarParaCobranca($cobranca, $empresa);

        return redirect()->route('admin.meu-plano.index')
            ->with('success', "Cobrança gerada! Após o pagamento, o plano {$plano->nome} será ativado automaticamente.");
    }

    /**
     * Downgrade: troca pra um plano de preço MENOR que o atual. Diferente
     * do upgrade, NÃO gera cobrança nova — efetiva imediatamente e o
     * próximo ciclo regular já cobra o valor do plano menor.
     *
     * Limites: se a empresa tem mais clientes/recompensas/parceiros/etc
     * que o plano alvo aceita, o downgrade ainda acontece, mas o sistema
     * passa a bloquear NOVOS cadastros via PlanoLimiteService::garantirCapacidade
     * até o consumo cair. O view do confirmar mostra os avisos antes.
     *
     * Módulos avançados perdidos somem na hora — empresa que estava usando
     * roleta num plano que não inclui mais já recebe redirect pra
     * /meu-plano via middleware modulo:roleta.
     */
    public function downgrade(Request $request, Plano $plano, PlanoLimiteService $limites)
    {
        $empresa = Auth::user()->empresa->loadMissing('plano', 'assinatura');

        if (!$plano->ativo) {
            return back()->with('error', 'Plano indisponível.');
        }
        $planoAtual = $empresa->plano;
        if (!$planoAtual) {
            // Sem plano atual, qualquer mudança é "upgrade" (passa pelo fluxo de cobrança)
            return redirect()->route('admin.meu-plano.upgrade', $plano);
        }
        if ((int) $plano->id === (int) $planoAtual->id) {
            return back()->with('error', 'Você já está nesse plano.');
        }
        if ($plano->preco_mensal >= $planoAtual->preco_mensal) {
            // Trata como upgrade — preço maior ou igual passa pelo fluxo
            // de cobrança pra cobrar a diferença/valor cheio do alvo.
            return redirect()->route('admin.meu-plano.upgrade', $plano);
        }

        // Bloqueia se consumo persistente excede o limite do alvo. Sem
        // isso, empresa com 150 clientes descia pra plano de 100 e
        // ficava trancada (PlanoLimiteService::garantirCapacidade impede
        // novos cadastros, mas os 150 existentes ficavam "excedendo"
        // indefinidamente — UX confusa e operação inconsistente).
        $compat = $limites->avisosCompatibilidade($empresa, $plano);
        if (!empty($compat['bloqueadores'])) {
            return back()->with('error',
                'Não dá pra descer pra '.$plano->nome.' agora: '
                .implode(' ', $compat['bloqueadores'])
            );
        }

        DB::transaction(function () use ($empresa, $plano) {
            $assinatura = Assinatura::where('empresa_id', $empresa->id)
                ->whereNotIn('status', ['cancelada'])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            // Cancela cobranças de upgrade pendentes pra outros planos —
            // cliente decidiu descer, não faz sentido manter cobrança pra
            // subir. Cobranças regulares (não-upgrade) seguem ativas.
            if ($assinatura) {
                Cobranca::where('assinatura_id', $assinatura->id)
                    ->where('status', 'pendente')
                    ->whereJsonContains('meta->upgrade', ['plano_alvo_id' => $assinatura->plano_id_pendente])
                    ->update(['status' => 'cancelado']);

                $assinatura->update([
                    'plano_id'          => $plano->id,
                    'plano_id_pendente' => null,
                    'valor_mensal'      => $plano->preco_mensal,
                ]);
            }

            $empresa->update(['plano_id' => $plano->id]);
        });

        return redirect()->route('admin.meu-plano.index')
            ->with('success', "Plano alterado pra {$plano->nome} (R$ "
                .number_format($plano->preco_mensal, 2, ',', '.')
                ."/mês). A próxima cobrança virá com esse valor — o valor pago do mês atual não é estornado.");
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
