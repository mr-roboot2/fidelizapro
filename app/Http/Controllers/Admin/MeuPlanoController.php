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

        $cobranca = DB::transaction(function () use ($empresa, $plano) {
            $assinatura = Assinatura::firstOrNew(
                ['empresa_id' => $empresa->id],
            );
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
            ]);

            $empresa->update(['plano_id' => $plano->id]);
            return $cobranca;
        });

        // Gera PIX fora da transação (chamada HTTP externa)
        $pix->gerarParaCobranca($cobranca, $empresa);

        return redirect()->route('admin.meu-plano.index')
            ->with('success', "Plano {$plano->nome} ativado! Pague o PIX abaixo pra liberar.");
    }
}
