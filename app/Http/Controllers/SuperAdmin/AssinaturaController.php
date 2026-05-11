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
            'plano_id' => 'required|exists:planos,id',
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
        $cobranca = \App\Models\Cobranca::findOrFail($cobrancaId);
        $service->marcarPaga($cobranca);
        return back()->with('success', 'Cobrança marcada como paga.');
    }

    public function cancelar(Assinatura $assinatura, AssinaturaService $service)
    {
        $service->cancelar($assinatura);
        return back()->with('success', 'Assinatura cancelada.');
    }
}
