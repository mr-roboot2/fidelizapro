<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Cobranca;
use App\Models\Empresa;
use App\Models\Plano;
use App\Services\AssinaturaService;
use Illuminate\Http\Request;

class AssinaturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Assinatura::with(['empresa', 'plano']);

        if ($status = $request->input('status')) $query->where('status', $status);

        $assinaturas = $query->latest()->paginate(20)->withQueryString();

        $resumo = [
            'ativas' => Assinatura::where('status', 'ativa')->count(),
            'trial' => Assinatura::where('status', 'trial')->count(),
            'inadimplentes' => Assinatura::where('status', 'inadimplente')->count(),
            'mrr' => Assinatura::whereIn('status', ['ativa', 'trial'])->sum('valor_mensal'),
        ];

        $proximasCobrancas = Cobranca::with('empresa:id,nome,telefone')
            ->where('status', 'pendente')
            ->whereBetween('vencimento', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->orderBy('vencimento')
            ->limit(20)
            ->get();

        $vencidas = Cobranca::with('empresa:id,nome,telefone')
            ->where('status', 'pendente')
            ->whereDate('vencimento', '<', now()->toDateString())
            ->orderBy('vencimento')
            ->limit(20)
            ->get();

        return view('super.assinaturas.index', compact('assinaturas', 'resumo', 'proximasCobrancas', 'vencidas'));
    }

    public function show(Assinatura $assinatura)
    {
        $assinatura->load('empresa', 'plano', 'cobrancas');
        return view('super.assinaturas.show', compact('assinatura'));
    }

    public function create()
    {
        $empresasSemAssinatura = Empresa::doesntHave('assinatura')->orderBy('nome')->get(['id', 'nome']);
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        $planos = Plano::where('ativo', true)->orderBy('preco_mensal')->get();
        return view('super.assinaturas.form', compact('empresas', 'empresasSemAssinatura', 'planos'));
    }

    public function store(Request $request, AssinaturaService $service)
    {
        $dados = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            // Aceita só plano ATIVO. Antes `exists:planos,id` aceitava
            // qualquer plano (mesmo desativado), super admin criava
            // assinatura num plano fora de catálogo.
            'plano_id' => ['required', \Illuminate\Validation\Rule::exists('planos', 'id')->where('ativo', true)],
            'gateway' => 'required|in:mock,asaas',
            'dias_trial' => 'required|integer|min:0|max:60',
        ]);

        $empresa = Empresa::findOrFail($dados['empresa_id']);
        $plano = Plano::findOrFail($dados['plano_id']);

        try {
            $assinatura = $service->criar($empresa, $plano, $dados['gateway'], (int) $dados['dias_trial']);
            return redirect()->route('super.assinaturas.show', $assinatura)
                ->with('success', 'Assinatura criada com sucesso!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Falha: '.$e->getMessage())->withInput();
        }
    }

    public function gerarCobranca(Assinatura $assinatura, AssinaturaService $service)
    {
        try {
            $cobranca = $service->gerarProximaCobranca($assinatura);
            return back()->with('success', "Cobrança gerada! Link: {$cobranca->link_pagamento}");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function marcarPaga(Request $request, AssinaturaService $service, $cobrancaId)
    {
        $cobranca = Cobranca::findOrFail($cobrancaId);
        $service->marcarPaga($cobranca);
        return back()->with('success', 'Cobrança marcada como paga.');
    }

    public function cobrancaShow(Cobranca $cobranca)
    {
        $cobranca->load('assinatura.plano', 'empresa');
        return view('super.assinaturas.cobranca_show', compact('cobranca'));
    }

    public function regerarPix(Cobranca $cobranca, \App\Services\Pix\PixService $pix)
    {
        if ($cobranca->status !== 'pendente') {
            return back()->with('error', 'Cobrança já não é mais pendente.');
        }
        $meta = $cobranca->meta ?? [];
        unset($meta['pix_qr_code'], $meta['pix_qr_code_svg'], $meta['pix_copia_cola'], $meta['pix_expira_em']);
        $cobranca->update(['meta' => $meta, 'gateway_charge_id' => null]);
        $pix->gerarParaCobranca($cobranca->fresh(), $cobranca->empresa);
        return back()->with('success', 'Novo PIX gerado.');
    }

    public function cancelar(Assinatura $assinatura, AssinaturaService $service)
    {
        $service->cancelar($assinatura);
        return back()->with('success', 'Assinatura cancelada.');
    }

    public function cancelarCobranca(Request $request, Cobranca $cobranca)
    {
        if ($cobranca->status === 'pago') {
            return back()->with('error', 'Não dá pra cancelar uma cobrança já paga.');
        }
        if ($cobranca->status === 'cancelado') {
            return back()->with('error', 'Cobrança já está cancelada.');
        }

        $forcar = $request->boolean('forcar_local');

        // Lock + recheck dentro da transaction: admin clicando "cancelar" em
        // 2 abas simultâneas executava ReverterUpgradePlano 2× (uma vez por
        // request). lockForUpdate serializa, e o segundo cai no early-return
        // status===cancelado.
        $resultado = \DB::transaction(function () use ($cobranca, $forcar) {
            $lockada = Cobranca::lockForUpdate()->find($cobranca->id);
            if (!$lockada || $lockada->status === 'cancelado') {
                return ['skipped' => true];
            }

            $cancelouNoGateway = true;
            if ($lockada->gateway_charge_id) {
                try {
                    $cancelouNoGateway = (new \App\Services\Pagamento\AsaasGateway())->cancelarCobranca($lockada);
                } catch (\Throwable $e) {
                    $cancelouNoGateway = false;
                    \Log::warning('Asaas: exception ao cancelar cobrança', [
                        'cobranca_id' => $lockada->id,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            if (!$cancelouNoGateway && !$forcar) {
                return ['cancelou_gateway' => false, 'aborted' => true];
            }

            $lockada->update(['status' => 'cancelado']);
            $reverteu = (new \App\Services\ReverterUpgradePlano())->executar($lockada);

            return [
                'cancelou_gateway' => $cancelouNoGateway,
                'reverteu' => $reverteu,
            ];
        });

        if ($resultado['skipped'] ?? false) {
            return back()->with('info', 'Cobrança já estava cancelada por outra ação.');
        }

        if ($resultado['aborted'] ?? false) {
            return back()->with('error',
                'Falha ao cancelar no Asaas. A cobrança continua ativa lá e localmente. '
                .'Para cancelar SÓ localmente (deixando viva no gateway, sob seu risco), '
                .'reenvie marcando "forçar cancelamento local".'
            );
        }

        $msgRev = ($resultado['reverteu'] ?? false) ? ' Plano revertido pro anterior.' : '';
        $msg = $resultado['cancelou_gateway']
            ? 'Cobrança cancelada (inclusive no gateway).'.$msgRev
            : '⚠️ Cobrança cancelada localmente, mas ainda ATIVA no Asaas — cancele manualmente no painel deles ou o cliente pode pagar e o crédito não cair aqui.'.$msgRev;
        return back()->with($resultado['cancelou_gateway'] ? 'success' : 'warning', $msg);
    }

    public function excluirCobranca(Cobranca $cobranca)
    {
        if ($cobranca->status === 'pago') {
            return back()->with('error', 'Não dá pra excluir uma cobrança já paga (use cancelar/estornar).');
        }

        if ($cobranca->gateway_charge_id) {
            try {
                (new \App\Services\Pagamento\AsaasGateway())->cancelarCobranca($cobranca);
            } catch (\Throwable $e) {
                // segue com a exclusão local mesmo se gateway falhar
            }
        }

        // Reverte o plano ANTES de deletar (precisa da meta da cobrança)
        $reverteu = (new \App\Services\ReverterUpgradePlano())->executar($cobranca);

        $cobranca->delete();
        return redirect()->route('super.assinaturas.index')
            ->with('success', 'Cobrança excluída.'.($reverteu ? ' Plano do lojista revertido pro anterior.' : ''));
    }
}
