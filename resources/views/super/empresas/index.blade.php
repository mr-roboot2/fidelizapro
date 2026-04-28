@extends('layouts.super')
@section('title', 'Empresas (tenants)')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row gap-3 justify-between">
        <form method="GET" class="flex-1 max-w-md">
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Buscar por nome, CNPJ, e-mail"
                       class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
        </form>
        <a href="{{ route('super.empresas.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-sm">
            <i class="ri-add-line"></i> Nova empresa
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Empresa</th>
                    <th class="text-left p-3">CNPJ</th>
                    <th class="text-right p-3">Clientes</th>
                    <th class="text-right p-3">Compras</th>
                    <th class="text-right p-3">Admins</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($empresas as $emp)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded shrink-0" style="background:{{ $emp->cor_primaria }}"></div>
                                <div>
                                    <p class="font-medium">{{ $emp->nome }}</p>
                                    <p class="text-xs text-slate-500">/{{ $emp->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-3 font-mono text-xs">{{ $emp->cnpj ?? '—' }}</td>
                        <td class="p-3 text-right">{{ $emp->clientes_count }}</td>
                        <td class="p-3 text-right">{{ $emp->compras_count }}</td>
                        <td class="p-3 text-right">{{ $emp->users_count }}</td>
                        <td class="p-3 text-center">
                            <form action="{{ route('super.empresas.toggle', $emp) }}" method="POST" class="inline">
                                @csrf
                                <button class="text-xs px-2 py-0.5 rounded-full {{ $emp->ativo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $emp->ativo ? 'Ativa' : 'Inativa' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-3 text-center text-xs space-x-1 whitespace-nowrap">
                            <form action="{{ route('super.impersonate.entrar', $emp) }}" method="POST" class="inline">
                                @csrf
                                <button class="text-rose-600 hover:underline" title="Entrar como admin desta empresa">
                                    <i class="ri-spy-line"></i> Acessar
                                </button>
                            </form>
                            <a href="{{ route('super.empresas.show', $emp) }}" class="text-indigo-600 hover:underline">Ver</a>
                            <a href="{{ route('super.empresas.edit', $emp) }}" class="text-slate-600 hover:underline">Editar</a>
                            <form action="{{ route('super.empresas.destroy', $emp) }}" method="POST" class="inline" onsubmit="return confirm('CUIDADO: isso apaga TODOS os clientes, compras, regras e histórico desta empresa. Continuar?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-400">Nenhuma empresa cadastrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $empresas->links() }}</div>
</div>
@endsection
