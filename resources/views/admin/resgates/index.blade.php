@extends('layouts.admin')
@section('title', 'Resgates')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 space-y-3">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="relative flex-1 min-w-[240px]">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Buscar por código, cliente, telefone, CPF ou recompensa..."
                       class="w-full pl-10 pr-9 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @if (request('q'))
                    <a href="?{{ http_build_query(array_filter(['status' => request('status')])) }}"
                       class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" title="Limpar">
                        <i class="ri-close-line"></i>
                    </a>
                @endif
            </div>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Buscar</button>
        </form>
        <div class="flex flex-wrap gap-2">
            @foreach (['' => 'Todos', 'pendente' => 'Pendentes', 'aprovado' => 'Aprovados', 'entregue' => 'Entregues', 'cancelado' => 'Cancelados'] as $v => $r)
                <a href="?{{ http_build_query(array_filter(['status' => $v, 'q' => request('q')])) }}"
                   class="px-3 py-1.5 rounded-full text-sm {{ (request('status') ?? '') === $v ? 'bg-indigo-600 text-white':'bg-slate-100' }}">
                    {{ $r }}
                </a>
            @endforeach
        </div>
        @if (request('q') || request('status'))
            <p class="text-xs text-slate-500">
                <i class="ri-filter-line"></i>
                {{ $resgates->total() }} {{ $resgates->total() === 1 ? 'resgate encontrado' : 'resgates encontrados' }}
                @if (request('q')) para <strong>"{{ request('q') }}"</strong> @endif
            </p>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Código</th>
                    <th class="text-left p-3">Cliente</th>
                    <th class="text-left p-3">Recompensa</th>
                    <th class="text-right p-3">Pontos</th>
                    <th class="text-left p-3">Data</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($resgates as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-mono text-xs">{{ $r->codigo }}</td>
                        <td class="p-3">{{ $r->cliente->nome }}</td>
                        <td class="p-3">{{ $r->recompensa->nome }}</td>
                        <td class="p-3 text-right">{{ number_format($r->pontos_usados, 0, ',', '.') }}</td>
                        <td class="p-3">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3 text-center">
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full',
                                'bg-amber-100 text-amber-700' => $r->status === 'pendente',
                                'bg-blue-100 text-blue-700' => $r->status === 'aprovado',
                                'bg-emerald-100 text-emerald-700' => $r->status === 'entregue',
                                'bg-slate-200 text-slate-600' => $r->status === 'cancelado',
                            ])>{{ ucfirst($r->status) }}</span>
                        </td>
                        <td class="p-3 text-center space-x-1">
                            @if ($r->status === 'pendente')
                                <form action="{{ route('admin.resgates.aprovar', $r) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="text-emerald-600 text-xs">Aprovar</button>
                                </form>
                                <form action="{{ route('admin.resgates.cancelar', $r) }}" method="POST" class="inline" onsubmit="return confirm('Cancelar e estornar pontos?')">
                                    @csrf
                                    <button class="text-rose-600 text-xs">Cancelar</button>
                                </form>
                            @elseif ($r->status === 'aprovado')
                                <form action="{{ route('admin.resgates.entregar', $r) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="text-emerald-600 text-xs">Marcar entregue</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.resgates.show', $r) }}" class="text-indigo-600 text-xs">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-400">Sem resgates.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $resgates->links() }}</div>
</div>
@endsection
