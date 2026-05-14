@extends('layouts.super')
@section('title', 'Tutoriais em vídeo')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-semibold">Central de ajuda — Tutoriais em vídeo</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Os tutoriais aparecem pras empresas em <code class="bg-slate-100 px-1 rounded">/admin → Central de ajuda</code>.
                Arraste pra reordenar.
            </p>
        </div>
        <a href="{{ route('super.tutoriais.create') }}"
           class="bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2 rounded-lg font-medium">
            <i class="ri-add-line"></i> Novo tutorial
        </a>
    </div>

    <ul id="lista-tutoriais" class="divide-y divide-slate-100">
        @forelse ($tutoriais as $tut)
            <li class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50" data-id="{{ $tut->id }}">
                <button type="button" class="handle cursor-grab text-slate-400 hover:text-slate-600 active:cursor-grabbing" title="Arraste para reordenar">
                    <i class="ri-draggable text-xl"></i>
                </button>
                <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                    <i class="{{ $tut->tipo_video === 'upload' ? 'ri-video-upload-line' : 'ri-youtube-line' }} text-rose-600 text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-slate-800 truncate">{{ $tut->titulo }}</p>
                        @if (!$tut->publicado)
                            <span class="text-[10px] uppercase tracking-wide bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded">Rascunho</span>
                        @else
                            <span class="text-[10px] uppercase tracking-wide bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded">Publicado</span>
                        @endif
                        @if ($tut->duracao)
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                <i class="ri-time-line"></i> {{ $tut->duracao }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">
                        {{ $tut->descricao ?: 'Sem descrição' }}
                    </p>
                </div>
                <form action="{{ route('super.tutoriais.toggle', $tut) }}" method="POST" class="inline">
                    @csrf
                    <button class="text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-lg" title="{{ $tut->publicado ? 'Despublicar' : 'Publicar' }}">
                        <i class="{{ $tut->publicado ? 'ri-eye-line' : 'ri-eye-off-line' }}"></i>
                    </button>
                </form>
                <a href="{{ route('super.tutoriais.edit', $tut) }}"
                   class="text-sm bg-rose-600 text-white px-4 py-2 rounded-lg hover:bg-rose-700">
                    <i class="ri-edit-line"></i> Editar
                </a>
            </li>
        @empty
            <li class="px-6 py-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="ri-video-line text-3xl text-slate-400"></i>
                </div>
                <p class="text-slate-500 font-medium mb-1">Nenhum tutorial cadastrado</p>
                <p class="text-xs text-slate-400 mb-4">Crie o primeiro clicando em "Novo tutorial" acima.</p>
            </li>
        @endforelse
    </ul>
</div>

@if ($tutoriais->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    const lista = document.getElementById('lista-tutoriais');
    if (!lista) return;
    new Sortable(lista, {
        handle: '.handle',
        animation: 150,
        onEnd() {
            const ids = Array.from(lista.querySelectorAll('li[data-id]')).map(li => li.dataset.id);
            fetch('{{ route('super.tutoriais.reordenar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids }),
            });
        },
    });
})();
</script>
@endif
@endsection
