@extends('layouts.admin')
@section('title', 'Equipe')
@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">Equipe</h2>
        <p class="text-xs text-slate-500">Cadastre gerentes e atendentes da sua loja. O atendente acessa o PWA da loja e o caixa rápido; o gerente tem acesso quase total (igual admin pra fins operacionais).</p>
    </div>
    <a href="{{ route('admin.equipe.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
        <i class="ri-user-add-line"></i> Novo membro
    </a>
</div>

<form method="GET" class="mb-4">
    <div class="flex gap-2">
        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome ou e-mail"
               class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm">
        <button class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white rounded-lg text-sm">
            <i class="ri-search-line"></i> Buscar
        </button>
    </div>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase tracking-wider">
            <tr>
                <th class="text-left px-4 py-3">Nome</th>
                <th class="text-left px-4 py-3">E-mail</th>
                <th class="text-left px-4 py-3">Função</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-right px-4 py-3">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($usuarios as $u)
                @php
                    $podeEditar = in_array($u->role, ['gerente', 'atendente'], true);
                    $souEu = $u->id === auth()->id();
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                {{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-slate-800">{{ $u->name }}</span>
                            @if ($souEu)
                                <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">você</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        @php
                            $cores = [
                                'admin'    => 'bg-rose-100 text-rose-700',
                                'gerente'  => 'bg-purple-100 text-purple-700',
                                'atendente'=> 'bg-emerald-100 text-emerald-700',
                            ];
                            $rotulos = [
                                'admin' => 'Administrador',
                                'gerente' => 'Gerente',
                                'atendente' => 'Atendente',
                            ];
                        @endphp
                        <span class="text-xs px-2 py-1 rounded font-medium {{ $cores[$u->role] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $rotulos[$u->role] ?? $u->role }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if ($u->ativo)
                            <span class="text-xs px-2 py-1 rounded font-medium bg-emerald-50 text-emerald-700">
                                <i class="ri-check-line"></i> Ativo
                            </span>
                        @else
                            <span class="text-xs px-2 py-1 rounded font-medium bg-slate-100 text-slate-600">
                                <i class="ri-pause-line"></i> Inativo
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if ($podeEditar)
                            <a href="{{ route('admin.equipe.edit', $u) }}" class="text-indigo-600 hover:underline text-xs font-medium">
                                <i class="ri-edit-line"></i> Editar
                            </a>
                            @if (!$souEu)
                                <form action="{{ route('admin.equipe.destroy', $u) }}" method="POST" class="inline-block"
                                      onsubmit="return confirm('Remover {{ addslashes($u->name) }} da equipe?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 hover:underline text-xs font-medium ml-2">
                                        <i class="ri-delete-bin-line"></i> Remover
                                    </button>
                                </form>
                            @endif
                        @else
                            <span class="text-xs text-slate-400" title="Contas de administrador só são gerenciadas pelo super admin">
                                <i class="ri-lock-line"></i> bloqueado
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                        <i class="ri-team-line text-4xl text-slate-300 block mb-2"></i>
                        Nenhum membro cadastrado ainda. Clique em "Novo membro" pra adicionar um gerente ou atendente.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $usuarios->links() }}</div>

@endsection
