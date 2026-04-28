@extends('layouts.super')
@section('title', 'Logs de auditoria')
@section('content')
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3">
    <select name="empresa_id" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        <option value="">Todas empresas</option>
        @foreach ($empresas as $e)
            <option value="{{ $e->id }}" @selected(request('empresa_id') == $e->id)>{{ $e->nome }}</option>
        @endforeach
    </select>
    <select name="user_id" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        <option value="">Todos usuários</option>
        @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
        @endforeach
    </select>
    <select name="acao" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        <option value="">Todas ações</option>
        @foreach ($acoes as $a)
            <option value="{{ $a }}" @selected(request('acao') === $a)>{{ $a }}</option>
        @endforeach
    </select>
    <input type="text" name="entidade" value="{{ request('entidade') }}" placeholder="Entidade (Cliente, Compra...)"
           class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
    <button class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm">Filtrar</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left p-3">Data/hora</th>
                <th class="text-left p-3">Usuário</th>
                <th class="text-left p-3">Empresa</th>
                <th class="text-left p-3">Ação</th>
                <th class="text-left p-3">Entidade</th>
                <th class="text-left p-3">Descrição</th>
                <th class="text-left p-3">IP</th>
                <th class="text-center p-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="p-3 text-xs">{{ $log->created_at->format('d/m H:i:s') }}</td>
                    <td class="p-3">{{ $log->user?->name ?? '—' }}</td>
                    <td class="p-3">{{ $log->empresa?->nome ?? '—' }}</td>
                    <td class="p-3">
                        <span @class([
                            'text-xs px-2 py-0.5 rounded-full',
                            'bg-emerald-100 text-emerald-700' => $log->acao === 'created',
                            'bg-blue-100 text-blue-700' => $log->acao === 'updated',
                            'bg-rose-100 text-rose-700' => $log->acao === 'deleted',
                            'bg-purple-100 text-purple-700' => $log->acao === 'login',
                            'bg-slate-100 text-slate-700' => !in_array($log->acao, ['created','updated','deleted','login']),
                        ])>{{ $log->acao }}</span>
                    </td>
                    <td class="p-3 text-xs">{{ $log->entidadeNomeCurto() }}{{ $log->entidade_id ? ' #'.$log->entidade_id : '' }}</td>
                    <td class="p-3 text-slate-600 text-xs truncate max-w-xs">{{ $log->descricao }}</td>
                    <td class="p-3 text-xs font-mono text-slate-500">{{ $log->ip }}</td>
                    <td class="p-3 text-center">
                        <a href="{{ route('super.auditoria.show', $log) }}" class="text-xs text-rose-600">Ver</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-6 text-center text-slate-400">Nenhum log encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $logs->links() }}</div>
</div>
@endsection
