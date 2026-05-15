@extends('layouts.super')
@section('title', 'Assinatura #'.$assinatura->id)
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-bold text-lg mb-3">{{ $assinatura->empresa->nome }}</h2>
        <span @class([
            'text-xs px-2 py-1 rounded-full',
            'bg-emerald-100 text-emerald-700' => $assinatura->status === 'ativa',
            'bg-amber-100 text-amber-700' => $assinatura->status === 'trial',
            'bg-rose-100 text-rose-700' => $assinatura->status === 'inadimplente',
            'bg-slate-200 text-slate-600' => $assinatura->status === 'cancelada',
        ])>{{ ucfirst($assinatura->status) }}</span>

        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Plano</dt><dd class="font-semibold">{{ $assinatura->plano->nome }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Valor/mês</dt><dd class="font-semibold text-emerald-600">R$ {{ number_format($assinatura->valor_mensal, 2, ',', '.') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Início</dt><dd>{{ $assinatura->inicio->format('d/m/Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Próximo vencimento</dt><dd>{{ $assinatura->proximo_vencimento?->format('d/m/Y') ?? '—' }}</dd></div>
            @if ($assinatura->trial_ate)
                <div class="flex justify-between"><dt class="text-slate-500">Trial até</dt><dd>{{ $assinatura->trial_ate->format('d/m/Y') }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-slate-500">Gateway</dt><dd>{{ $assinatura->gateway }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Customer ID</dt><dd class="font-mono text-xs">{{ $assinatura->gateway_customer_id }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Subscription ID</dt><dd class="font-mono text-xs">{{ $assinatura->gateway_subscription_id }}</dd></div>
        </dl>

        <div class="mt-6 space-y-2">
            <form action="{{ route('super.assinaturas.gerar-cobranca', $assinatura) }}" method="POST">
                @csrf
                <button class="w-full py-2 bg-rose-600 text-white rounded-lg text-sm">
                    <i class="ri-bill-line"></i> Gerar próxima cobrança
                </button>
            </form>
            @if ($assinatura->status !== 'cancelada')
                <form action="{{ route('super.assinaturas.cancelar', $assinatura) }}" method="POST" onsubmit="return confirm('Cancelar assinatura?')">
                    @csrf
                    <button class="w-full py-2 bg-slate-200 rounded-lg text-sm">
                        <i class="ri-close-line"></i> Cancelar assinatura
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold">Histórico de cobranças</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Vencimento</th>
                        <th class="text-right p-3">Valor</th>
                        <th class="text-center p-3">Status</th>
                        <th class="text-left p-3">Pago em</th>
                        <th class="text-center p-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assinatura->cobrancas as $c)
                        <tr>
                            <td class="p-3 text-xs">{{ $c->vencimento->format('d/m/Y') }}</td>
                            <td class="p-3 text-right">R$ {{ number_format($c->valor, 2, ',', '.') }}</td>
                            <td class="p-3 text-center">
                                <span @class([
                                    'text-xs px-2 py-0.5 rounded-full',
                                    'bg-emerald-100 text-emerald-700' => $c->status === 'pago',
                                    'bg-amber-100 text-amber-700' => $c->status === 'pendente' && !$c->vencida(),
                                    'bg-rose-100 text-rose-700' => $c->status === 'vencido' || ($c->status === 'pendente' && $c->vencida()),
                                ])>{{ $c->status === 'pendente' && $c->vencida() ? 'Vencido' : ucfirst($c->status) }}</span>
                            </td>
                            <td class="p-3 text-xs">{{ $c->pago_em?->format('d/m/Y') ?? '—' }}</td>
                            <td class="p-3 text-center text-xs">
                                <div class="inline-flex items-center gap-2">
                                    @if ($c->link_pagamento)
                                        <a href="{{ $c->link_pagamento }}" target="_blank" class="text-rose-600">
                                            <i class="ri-link"></i> Pagar
                                        </a>
                                    @endif
                                    @if ($c->status === 'pendente')
                                        <form action="{{ route('super.cobrancas.marcar-paga', $c) }}" method="POST" class="inline">
                                            @csrf
                                            <button class="text-emerald-600">Marcar paga</button>
                                        </form>
                                    @endif
                                    @if ($c->status !== 'pago')
                                        @if ($c->status !== 'cancelado')
                                            <form action="{{ route('super.cobrancas.cancelar', $c) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Cancelar essa cobrança?')">
                                                @csrf
                                                <button title="Cancelar" class="text-amber-600"><i class="ri-close-circle-line"></i></button>
                                            </form>
                                        @endif
                                        <form action="{{ route('super.cobrancas.excluir', $c) }}" method="POST" class="inline"
                                              onsubmit="return confirm('EXCLUIR essa cobrança permanentemente?')">
                                            @csrf @method('DELETE')
                                            <button title="Excluir" class="text-rose-600"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-400">Nenhuma cobrança.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
