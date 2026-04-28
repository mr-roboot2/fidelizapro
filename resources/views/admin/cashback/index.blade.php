@extends('layouts.admin')
@section('title', 'Cashback')
@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-slate-500">Total creditado</p>
        <p class="text-2xl font-bold text-emerald-600">R$ {{ number_format($totalCreditado, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-slate-500">Total utilizado</p>
        <p class="text-2xl font-bold text-rose-600">R$ {{ number_format($totalUsado, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-slate-500">Em circulação</p>
        <p class="text-2xl font-bold text-indigo-600">R$ {{ number_format($emCirculacao, 2, ',', '.') }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 font-semibold">Movimentações recentes</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Data</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">Origem</th>
                    <th class="text-left p-3">Descrição</th>
                    <th class="text-right p-3">Valor</th>
                    <th class="text-right p-3">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($movimentos as $m)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3">{{ $m->cliente->nome }}</td>
                        <td class="p-3"><span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $m->origem }}</span></td>
                        <td class="p-3 text-slate-600">{{ $m->descricao }}</td>
                        <td class="p-3 text-right font-semibold {{ $m->tipo === 'credito' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $m->tipo === 'credito' ? '+' : '−' }}R$ {{ number_format($m->valor, 2, ',', '.') }}
                        </td>
                        <td class="p-3 text-right text-slate-500">R$ {{ number_format($m->saldo_posterior, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-400">Sem movimentações.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $movimentos->links() }}</div>
</div>
@endsection
