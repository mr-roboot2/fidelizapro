@extends('layouts.super')
@section('title', 'Usuários do sistema')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row gap-3 justify-between">
        <form method="GET" class="flex flex-wrap gap-2 flex-1">
            <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar"
                   class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <select name="empresa_id" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                <option value="">Todas empresas</option>
                @foreach ($empresas as $e)
                    <option value="{{ $e->id }}" @selected(request('empresa_id') == $e->id)>{{ $e->nome }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-slate-200 rounded-lg text-sm">Filtrar</button>
        </form>
        <a href="{{ route('super.users.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-sm">
            <i class="ri-user-add-line"></i> Novo usuário
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Nome</th>
                    <th class="text-left p-3">E-mail</th>
                    <th class="text-left p-3">Empresa</th>
                    <th class="text-left p-3">Papel</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-medium">{{ $u->name }}</td>
                        <td class="p-3 text-slate-600">{{ $u->email }}</td>
                        <td class="p-3">{{ $u->empresa?->nome ?? '—' }}</td>
                        <td class="p-3">
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full',
                                'bg-rose-100 text-rose-700' => $u->role === 'super_admin',
                                'bg-indigo-100 text-indigo-700' => $u->role === 'admin',
                                'bg-blue-100 text-blue-700' => $u->role === 'gerente',
                                'bg-slate-100 text-slate-700' => $u->role === 'atendente',
                            ])>{{ $u->role }}</span>
                        </td>
                        <td class="p-3 text-center">
                            @if ($u->ativo)
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Ativo</span>
                            @else
                                <span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">Inativo</span>
                            @endif
                        </td>
                        <td class="p-3 text-center text-xs space-x-1">
                            <a href="{{ route('super.users.edit', $u) }}" class="text-indigo-600">Editar</a>
                            <form action="{{ route('super.users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Excluir usuário?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-400">Nenhum usuário.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $users->links() }}</div>
</div>
@endsection
