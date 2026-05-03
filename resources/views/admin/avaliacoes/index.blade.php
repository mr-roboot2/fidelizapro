@extends('layouts.admin')
@section('title', 'Avaliações')
@section('content')

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wider">Total</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ $resumo['total'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wider">Nota média</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($resumo['media'], 1, ',', '.') }} <span class="text-sm font-normal text-slate-500">/ 5</span></p>
    </div>
    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
        <p class="text-xs text-emerald-700 uppercase tracking-wider">Promotores (4-5)</p>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $resumo['promotores'] }}</p>
    </div>
    <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
        <p class="text-xs text-amber-700 uppercase tracking-wider">Neutros (3)</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ $resumo['neutros'] }}</p>
    </div>
    <div class="bg-rose-50 rounded-xl p-4 border border-rose-100">
        <p class="text-xs text-rose-700 uppercase tracking-wider">Detratores (1-2)</p>
        <p class="text-2xl font-bold text-rose-700 mt-1">{{ $resumo['detratores'] }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm">
    <form method="GET" class="px-6 py-4 border-b border-slate-200 flex flex-wrap gap-2 items-center">
        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar cliente..."
               class="flex-1 min-w-48 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        <select name="nota" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <option value="">Todas as notas</option>
            <option value="promotores" @selected(request('nota')==='promotores')>Promotores (4-5★)</option>
            <option value="neutros" @selected(request('nota')==='neutros')>Neutros (3★)</option>
            <option value="detratores" @selected(request('nota')==='detratores')>Detratores (1-2★)</option>
            <option disabled>──</option>
            @foreach([5,4,3,2,1] as $n)
                <option value="{{ $n }}" @selected(request('nota')==(string)$n)>{{ $n }} estrelas</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="ri-filter-3-line"></i> Filtrar
        </button>
        @if (request('busca') || request('nota'))
            <a href="{{ route('admin.avaliacoes.index') }}" class="text-sm text-slate-500 hover:text-slate-800 px-2">Limpar</a>
        @endif
    </form>

    <ul class="divide-y divide-slate-100">
        @forelse ($avaliacoes as $av)
            <li class="px-6 py-4 flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-semibold text-slate-600 flex-shrink-0">
                    {{ strtoupper(substr($av->cliente->nome ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-slate-800">{{ $av->cliente->nome ?? 'Cliente removido' }}</p>
                        <span class="text-xs text-slate-500">&middot;</span>
                        <span class="text-xs text-slate-500">{{ $av->cliente->telefone ?? '—' }}</span>
                        <span class="text-xs text-slate-500">&middot;</span>
                        <span class="text-xs text-slate-500">{{ $av->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-1 mt-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="ri-star-{{ $i <= $av->nota ? 'fill text-amber-400' : 'line text-slate-300' }} text-base"></i>
                        @endfor
                        <span class="text-xs ml-1 px-2 py-0.5 rounded-full
                            {{ $av->isPromotor() ? 'bg-emerald-100 text-emerald-700' : ($av->isDetrator() ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $av->isPromotor() ? 'Promotor' : ($av->isDetrator() ? 'Detrator' : 'Neutro') }}
                        </span>
                    </div>
                    @if ($av->comentario)
                        <p class="text-sm text-slate-700 mt-2 leading-relaxed">{{ $av->comentario }}</p>
                    @else
                        <p class="text-xs text-slate-400 italic mt-2">Sem comentário</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.avaliacoes.destroy', $av) }}"
                      onsubmit="return confirm('Excluir esta avaliação?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg text-xs">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </form>
            </li>
        @empty
            <li class="px-6 py-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="ri-star-line text-3xl text-slate-400"></i>
                </div>
                <p class="text-slate-500 font-medium">Nenhuma avaliação encontrada</p>
            </li>
        @endforelse
    </ul>

    @if ($avaliacoes->hasPages())
        <div class="p-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
            <p class="text-xs text-slate-500">
                Mostrando {{ $avaliacoes->firstItem() }}–{{ $avaliacoes->lastItem() }} de {{ $avaliacoes->total() }} avaliações
            </p>
            <div>{{ $avaliacoes->links() }}</div>
        </div>
    @else
        <div class="p-4 border-t border-slate-100">
            <p class="text-xs text-slate-500 text-center">{{ $avaliacoes->total() }} {{ $avaliacoes->total() === 1 ? 'avaliação' : 'avaliações' }} no total</p>
        </div>
    @endif
</div>
@endsection
