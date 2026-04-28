@extends('layouts.admin')
@section('title', 'Histórico de transações')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <form method="GET" class="p-4 border-b border-slate-200 flex flex-wrap gap-2">
        <select name="tipo" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <option value="">Tipo (todos)</option>
            <option value="credito" @selected(request('tipo')==='credito')>Crédito</option>
            <option value="debito" @selected(request('tipo')==='debito')>Débito</option>
        </select>
        <select name="origem" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <option value="">Origem (todas)</option>
            @foreach (['compra','resgate','manual','indicacao','aniversario','cadastro'] as $o)
                <option value="{{ $o }}" @selected(request('origem')===$o)>{{ ucfirst($o) }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-slate-200 rounded-lg text-sm">Filtrar</button>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Data</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">Origem</th>
                    <th class="text-left p-3">Descrição</th>
                    <th class="text-right p-3">Pontos</th>
                    <th class="text-right p-3">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transacoes as $t)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3">{{ $t->cliente->nome }}</td>
                        <td class="p-3"><span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $t->origem }}</span></td>
                        <td class="p-3 text-slate-600">{{ $t->descricao }}</td>
                        <td class="p-3 text-right font-semibold {{ $t->tipo === 'credito' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $t->tipo === 'credito' ? '+' : '−' }}{{ number_format($t->pontos, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right text-slate-500">{{ number_format($t->saldo_posterior, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-400">Sem transações.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $transacoes->links() }}</div>
</div>
@endsection
