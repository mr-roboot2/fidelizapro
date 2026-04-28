@extends('layouts.admin')
@section('title', 'Parceiros')
@section('content')
<div class="mb-4 flex justify-between items-center">
    <a href="{{ route('admin.parceiros.relatorio') }}" class="text-sm text-indigo-600 hover:underline">
        <i class="ri-bar-chart-line"></i> Ver relatório de cupons
    </a>
    <a href="{{ route('admin.parceiros.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
        <i class="ri-add-line"></i> Novo parceiro
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($parceiros as $p)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="aspect-video bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white">
                @if ($p->logo)
                    <img src="{{ asset('storage/'.$p->logo) }}" class="w-full h-full object-cover">
                @else
                    <i class="ri-building-line text-5xl"></i>
                @endif
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold">{{ $p->nome }}</h3>
                    @if (!$p->ativo)<span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">Inativo</span>@endif
                </div>
                @if ($p->categoria)
                    <p class="text-xs text-slate-500 mt-1">{{ $p->categoria }}</p>
                @endif
                <p class="text-sm text-slate-500 line-clamp-2 mt-2">{{ $p->descricao }}</p>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-slate-500">{{ $p->beneficios_ativos_count }} benefício(s)</span>
                    <a href="{{ route('admin.parceiros.show', $p) }}" class="text-indigo-600 font-medium">
                        Gerenciar →
                    </a>
                </div>
            </div>
        </div>
    @empty
        <p class="text-slate-400 col-span-full text-center py-10">Nenhum parceiro cadastrado.</p>
    @endforelse
</div>
<div class="mt-4">{{ $parceiros->links() }}</div>
@endsection
