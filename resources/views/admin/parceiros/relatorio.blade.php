@extends('layouts.admin')
@section('title', 'Relatório de cupons')
@section('content')
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="text-xs text-slate-500">De</label>
        <input type="date" name="de" value="{{ $de }}" class="block px-3 py-2 border border-slate-300 rounded-lg text-sm">
    </div>
    <div>
        <label class="text-xs text-slate-500">Até</label>
        <input type="date" name="ate" value="{{ $ate }}" class="block px-3 py-2 border border-slate-300 rounded-lg text-sm">
    </div>
    <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm">Filtrar</button>
</form>

<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <p class="text-xs text-slate-500">Cupons gerados</p>
        <p class="text-2xl font-bold text-indigo-600">{{ $totalGerados }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <p class="text-xs text-slate-500">Cupons usados</p>
        <p class="text-2xl font-bold text-emerald-600">{{ $totalUsados }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <p class="text-xs text-slate-500">Taxa de uso</p>
        <p class="text-2xl font-bold text-purple-600">
            {{ $totalGerados > 0 ? round(($totalUsados / $totalGerados) * 100, 1) : 0 }}%
        </p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left p-3">Código</th>
                <th class="text-left p-3">Cliente</th>
                <th class="text-left p-3">Parceiro</th>
                <th class="text-left p-3">Benefício</th>
                <th class="text-left p-3">Gerado em</th>
                <th class="text-center p-3">Status</th>
                <th class="text-left p-3">Usado em</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($cupons as $c)
                <tr class="hover:bg-slate-50">
                    <td class="p-3 font-mono text-xs">{{ $c->codigo }}</td>
                    <td class="p-3">{{ $c->cliente->nome }}</td>
                    <td class="p-3">{{ $c->beneficio->parceiro->nome }}</td>
                    <td class="p-3">{{ $c->beneficio->nome }}</td>
                    <td class="p-3 text-xs">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td class="p-3 text-center">
                        <span @class([
                            'text-xs px-2 py-0.5 rounded-full',
                            'bg-amber-100 text-amber-700' => $c->status === 'disponivel',
                            'bg-emerald-100 text-emerald-700' => $c->status === 'usado',
                            'bg-slate-200 text-slate-600' => $c->status === 'expirado',
                        ])>{{ ucfirst($c->status) }}</span>
                    </td>
                    <td class="p-3 text-xs">{{ $c->usado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-center text-slate-400">Nenhum cupom no período.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $cupons->links() }}</div>
</div>
@endsection
