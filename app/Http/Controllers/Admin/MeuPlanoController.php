<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Plano;
use App\Services\PlanoLimiteService;
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
    public function upgrade(Request $request, Plano $plano)
    {
        $empresa = Auth::user()->empresa;
        if (!$plano->ativo) {
            return back()->with('error', 'Plano indisponível.');
        }

        DB::transaction(function () use ($empresa, $plano) {
            $assinatura = Assinatura::firstOrNew(
                ['empresa_id' => $empresa->id],
            );
            $assinatura->fill([
                'plano_id'           => $plano->id,
                'status'             => $assinatura->exists ? $assinatura->status : 'ativa',
                'gateway'            => 'mock',
                'valor_mensal'       => $plano->preco_mensal,
                'inicio'             => $assinatura->exists ? $assinatura->inicio : now(),
                'proximo_vencimento' => now()->addDays(7), // 7 dias pra pagar
            ])->save();

            // Cria cobrança pendente
            Cobranca::create([
                'assinatura_id' => $assinatura->id,
                'empresa_id'    => $empresa->id,
                'valor'         => $plano->preco_mensal,
                'vencimento'    => now()->addDays(7),
                'status'        => 'pendente',
            ]);

            // Atualiza o plano padrão da empresa (espelho)
            $empresa->update(['plano_id' => $plano->id]);
        });

        return redirect()->route('admin.meu-plano.index')
            ->with('success', "Plano {$plano->nome} ativado! Pague a cobrança pendente em até 7 dias.");
    }
}
