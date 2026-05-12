@extends('layouts.admin')
@section('title', 'Compras')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row gap-3 justify-between">
        <form method="GET" class="flex flex-wrap gap-2 flex-1">
            <input type="date" name="de" value="{{ request('de') }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <input type="date" name="ate" value="{{ request('ate') }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <button class="px-4 py-2 bg-slate-200 rounded-lg text-sm">Filtrar</button>
        </form>
        <a href="{{ route('admin.caixa.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
            <i class="ri-cash-line"></i> Lançar compra
        </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 border-b border-slate-200 bg-slate-50">
        <div><p class="text-xs text-slate-500">Compras</p><p class="font-bold">{{ $compras->total() }}</p></div>
        <div><p class="text-xs text-slate-500">Faturamento</p><p class="font-bold text-emerald-600">R$ {{ number_format($totalValor, 2, ',', '.') }}</p></div>
        <div><p class="text-xs text-slate-500">Pontos gerados</p><p class="font-bold text-amber-600">{{ number_format($totalPontos, 0, ',', '.') }}</p></div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Data</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-right p-3">Valor</th>
                    <th class="text-right p-3">Pontos</th>
                    <th class="text-right p-3">Cashback</th>
                    <th class="text-left p-3">Origem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($compras as $c)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3">{{ $c->cliente->nome }}</td>
                        <td class="p-3 text-right font-semibold text-emerald-600">R$ {{ number_format($c->valor, 2, ',', '.') }}</td>
                        <td class="p-3 text-right text-amber-600">+{{ number_format($c->pontos_gerados, 0, ',', '.') }}</td>
                        <td class="p-3 text-right">R$ {{ number_format($c->cashback_gerado, 2, ',', '.') }}</td>
                        <td class="p-3"><span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $c->origem }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-400">Sem compras.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $compras->links() }}</div>
</div>
@endsection
