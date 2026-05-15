@extends('layouts.super')
@section('title', 'Assinaturas SaaS')
@section('content')

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['MRR (Receita Mensal)', 'R$ '.number_format($resumo['mrr'], 2, ',', '.'), 'ri-money-dollar-circle-line', 'from-emerald-500 to-teal-500'],
            ['Assinaturas ativas', $resumo['ativas'], 'ri-check-double-line', 'from-blue-500 to-cyan-500'],
            ['Em trial', $resumo['trial'], 'ri-time-line', 'from-amber-500 to-orange-500'],
            ['Inadimplentes', $resumo['inadimplentes'], 'ri-error-warning-line', 'from-rose-500 to-pink-500'],
        ];
    @endphp
    @foreach ($cards as [$titulo, $valor, $icone, $grad])
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-500">{{ $titulo }}</p>
                    <p class="text-xl font-bold mt-1">{{ $valor }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $grad }} text-white flex items-center justify-center">
                    <i class="{{ $icone }}"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if ($vencidas->isNotEmpty() || $proximasCobrancas->isNotEmpty())
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    @if ($vencidas->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border-l-4 border-rose-500">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-rose-700"><i class="ri-error-warning-line"></i> Cobranças vencidas</h3>
                    <p class="text-xs text-slate-500">{{ $vencidas->count() }} em atraso</p>
                </div>
                <p class="text-sm font-bold text-rose-600">R$ {{ number_format($vencidas->sum('valor'), 2, ',', '.') }}</p>
            </div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                @foreach ($vencidas as $c)
                    <div class="p-3 hover:bg-slate-50 flex items-center justify-between gap-2 text-sm">
                        <a href="{{ route('super.cobrancas.show', $c) }}" class="min-w-0 flex-1">
                            <p class="font-medium text-slate-700 truncate">{{ $c->empresa->nome ?? '—' }}</p>
                            <p class="text-xs text-rose-600">venceu {{ $c->vencimento->format('d/m/Y') }} ({{ (int) $c->vencimento->diffInDays(now()) }}d atrás)</p>
                        </a>
                        <p class="font-semibold whitespace-nowrap">R$ {{ number_format($c->valor, 2, ',', '.') }}</p>
                        @include('super.assinaturas._cobranca_actions', ['c' => $c])
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($proximasCobrancas->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border-l-4 border-amber-500">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-amber-700"><i class="ri-time-line"></i> Vencendo em 7 dias</h3>
                    <p class="text-xs text-slate-500">{{ $proximasCobrancas->count() }} cobranças</p>
                </div>
                <p class="text-sm font-bold text-amber-600">R$ {{ number_format($proximasCobrancas->sum('valor'), 2, ',', '.') }}</p>
            </div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                @foreach ($proximasCobrancas as $c)
                    <div class="p-3 hover:bg-slate-50 flex items-center justify-between gap-2 text-sm">
                        <a href="{{ route('super.cobrancas.show', $c) }}" class="min-w-0 flex-1">
                            <p class="font-medium text-slate-700 truncate">{{ $c->empresa->nome ?? '—' }}</p>
                            <p class="text-xs text-slate-500">vence {{ $c->vencimento->format('d/m/Y') }} (em {{ (int) now()->diffInDays($c->vencimento) }}d)</p>
                        </a>
                        <p class="font-semibold whitespace-nowrap">R$ {{ number_format($c->valor, 2, ',', '.') }}</p>
                        @include('super.assinaturas._cobranca_actions', ['c' => $c])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif

<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 flex justify-between items-center">
        <div class="flex flex-wrap gap-2">
            @foreach (['' => 'Todas', 'ativa' => 'Ativas', 'trial' => 'Trial', 'inadimplente' => 'Inadimplentes', 'cancelada' => 'Canceladas'] as $v => $r)
                <a href="?status={{ $v }}"
                   class="px-3 py-1 rounded-full text-xs {{ request('status') === $v ? 'bg-rose-600 text-white' : 'bg-slate-100' }}">
                    {{ $r }}
                </a>
            @endforeach
        </div>
        <a href="{{ route('super.assinaturas.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-lg text-sm">
            <i class="ri-add-line"></i> Nova assinatura
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Empresa</th>
                    <th class="text-left p-3">Plano</th>
                    <th class="text-right p-3">Valor/mês</th>
                    <th class="text-left p-3">Próximo venc.</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3">Gateway</th>
                    <th class="text-center p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assinaturas as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-medium">{{ $a->empresa->nome }}</td>
                        <td class="p-3">{{ $a->plano->nome }}</td>
                        <td class="p-3 text-right font-semibold text-emerald-600">R$ {{ number_format($a->valor_mensal, 2, ',', '.') }}</td>
                        <td class="p-3 text-xs">{{ $a->proximo_vencimento?->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-3 text-center">
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full',
                                'bg-emerald-100 text-emerald-700' => $a->status === 'ativa',
                                'bg-amber-100 text-amber-700' => $a->status === 'trial',
                                'bg-rose-100 text-rose-700' => $a->status === 'inadimplente',
                                'bg-slate-200 text-slate-600' => $a->status === 'cancelada',
                                'bg-blue-100 text-blue-700' => $a->status === 'pausada',
                            ])>{{ ucfirst($a->status) }}</span>
                        </td>
                        <td class="p-3 text-center text-xs">{{ $a->gateway }}</td>
                        <td class="p-3 text-center">
                            <a href="{{ route('super.assinaturas.show', $a) }}" class="text-xs text-rose-600">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-400">Nenhuma assinatura.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $assinaturas->links() }}</div>
</div>
@endsection
