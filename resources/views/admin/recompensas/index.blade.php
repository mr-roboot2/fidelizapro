@extends('layouts.admin')
@section('title', 'Recompensas')
@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('admin.recompensas.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
        <i class="ri-add-line"></i> Nova recompensa
    </a>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($recompensas as $r)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="aspect-video bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white">
                @if ($r->imagem)
                    <img src="{{ asset('storage/'.$r->imagem) }}" class="w-full h-full object-cover">
                @else
                    <i class="ri-gift-line text-5xl"></i>
                @endif
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold">{{ $r->nome }}</h3>
                    @if ($r->destaque)<span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Destaque</span>@endif
                </div>
                <p class="text-sm text-slate-500 line-clamp-2 mt-1">{{ $r->descricao }}</p>
                <div class="flex items-center justify-between mt-3">
                    <span class="font-bold text-amber-600">{{ number_format($r->custo_pontos, 0, ',', '.') }} pts</span>
                    <span class="text-xs text-slate-500">Estoque: {{ $r->estoque ?? '∞' }}</span>
                </div>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('admin.recompensas.edit', $r) }}" class="flex-1 text-center text-sm py-1.5 bg-slate-100 rounded">Editar</a>
                    <form action="{{ route('admin.recompensas.destroy', $r) }}" method="POST" onsubmit="return confirm('Excluir?')">
                        @csrf @method('DELETE')
                        <button class="text-sm py-1.5 px-3 bg-rose-100 text-rose-700 rounded">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p class="text-slate-400 col-span-full text-center py-10">Nenhuma recompensa cadastrada.</p>
    @endforelse
</div>
<div class="mt-4">{{ $recompensas->links() }}</div>
@endsection
