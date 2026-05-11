@extends('layouts.admin')
@section('title', 'Sorteios')
@section('content')
<div class="mb-4 flex justify-end gap-2">
    <a href="{{ route('admin.sorteios.metricas') }}"
       class="inline-flex items-center gap-2 px-3 py-2 bg-slate-900 text-white rounded-lg text-sm hover:bg-slate-800">
        <i class="ri-bar-chart-2-line"></i> Ver métricas
    </a>
    <a href="{{ route('admin.sorteios.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
        <i class="ri-add-line"></i> Novo sorteio
    </a>
</div>

@if (session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b">
            <tr class="text-left">
                <th class="p-3">Nome</th>
                <th>Status</th>
                <th>Data sorteio</th>
                <th class="text-right">Bilhetes</th>
                <th>Prêmio</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sorteios as $s)
                <tr class="border-b last:border-b-0 hover:bg-slate-50">
                    <td class="p-3 font-medium text-slate-800">
                        <a href="{{ route('admin.sorteios.show', $s) }}" class="hover:underline">{{ $s->nome }}</a>
                        @if ($s->descricao)<p class="text-xs text-slate-400 truncate max-w-xs">{{ $s->descricao }}</p>@endif
                    </td>
                    <td>
                        <span @class([
                            'text-xs px-2 py-0.5 rounded-full',
                            'bg-emerald-100 text-emerald-700' => $s->status === 'ativo',
                            'bg-amber-100 text-amber-700' => $s->status === 'planejado',
                            'bg-indigo-100 text-indigo-700' => $s->status === 'sorteado',
                            'bg-slate-700 text-white' => $s->status === 'finalizado',
                            'bg-slate-200 text-slate-500' => $s->status === 'cancelado',
                        ])>{{ ucfirst($s->status) }}</span>
                    </td>
                    <td>{{ $s->data_sorteio->format('d/m/Y') }}</td>
                    <td class="text-right font-semibold">{{ $s->bilhetes_count }}</td>
                    <td class="text-slate-600 text-xs">
                        @if ($s->recompensa_id)
                            {{ $s->recompensa->nome ?? '—' }}
                        @elseif ($s->valor_estimado)
                            R$ {{ number_format($s->valor_estimado, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.sorteios.show', $s) }}" class="text-indigo-600 text-xs hover:underline">Ver</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-400">Nenhum sorteio criado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $sorteios->links() }}</div>
@endsection
