@extends('layouts.admin')
@section('title', 'Clientes')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row gap-3 justify-between">
        <form method="GET" class="flex-1 max-w-md">
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Buscar por nome, telefone, e-mail ou CPF"
                       class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
        </form>
        <a href="{{ route('admin.clientes.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
            <i class="ri-add-line"></i> Novo cliente
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Nome</th>
                    <th class="text-left p-3">Telefone</th>
                    <th class="text-right p-3">Pontos</th>
                    <th class="text-right p-3">Cashback</th>
                    <th class="text-right p-3">Total gasto</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($clientes as $cli)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3">
                            <div class="font-medium">{{ $cli->nome }}</div>
                            <div class="text-xs text-slate-500">{{ $cli->email }}</div>
                        </td>
                        <td class="p-3">{{ $cli->telefone }}</td>
                        <td class="p-3 text-right font-semibold text-amber-600">
                            {{ number_format($cli->pontos_atual, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right text-emerald-600">
                            R$ {{ number_format($cli->cashback_atual, 2, ',', '.') }}
                        </td>
                        <td class="p-3 text-right">R$ {{ number_format($cli->total_gasto, 2, ',', '.') }}</td>
                        <td class="p-3 text-center">
                            @if ($cli->ativo)
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Ativo</span>
                            @else
                                <span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">Inativo</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <a href="{{ route('admin.clientes.show', $cli) }}" class="text-indigo-600 hover:underline mr-2">Ver</a>
                            <a href="{{ route('admin.clientes.edit', $cli) }}" class="text-slate-600 hover:underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-400">Nenhum cliente encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $clientes->links() }}</div>
</div>
@endsection
