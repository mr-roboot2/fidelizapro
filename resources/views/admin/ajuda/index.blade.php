@extends('layouts.admin')
@section('title', 'Central de ajuda')
@section('content')
<div class="space-y-4" x-data="{ aberto: null }">
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-rose-500 text-white rounded-xl p-6 flex items-center gap-4 shadow-sm">
        <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center shrink-0">
            <i class="ri-question-answer-line text-3xl"></i>
        </div>
        <div class="flex-1">
            <h2 class="text-xl font-semibold">Aprenda a usar o sistema</h2>
            <p class="text-sm text-white/85 mt-0.5">Vídeos curtos explicando cada parte do painel. Clique em um item pra assistir.</p>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-xl shadow-sm p-3 flex items-center gap-2">
        <i class="ri-search-line text-slate-400 ml-2"></i>
        <input type="text" name="busca" value="{{ $busca }}"
               placeholder="Buscar tutorial (ex: caixa, roleta, cliente...)"
               class="flex-1 px-2 py-2 text-sm focus:outline-none">
        @if ($busca)
            <a href="{{ route('admin.ajuda.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2">
                <i class="ri-close-line"></i> Limpar
            </a>
        @endif
        <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
            Buscar
        </button>
    </form>

    @forelse ($tutoriais as $tut)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <button type="button"
                    @click="aberto = (aberto === {{ $tut->id }} ? null : {{ $tut->id }})"
                    class="w-full px-5 py-4 flex items-center gap-4 text-left hover:bg-slate-50 transition">
                <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                    <i class="ri-play-circle-line text-indigo-600 text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-slate-800">{{ $tut->titulo }}</p>
                        @if ($tut->duracao)
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                <i class="ri-time-line"></i> {{ $tut->duracao }}
                            </span>
                        @endif
                    </div>
                    @if ($tut->descricao)
                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $tut->descricao }}</p>
                    @endif
                </div>
                <i class="ri-arrow-down-s-line text-slate-400 text-xl transition" :class="aberto === {{ $tut->id }} ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="aberto === {{ $tut->id }}" x-cloak class="border-t border-slate-100 px-5 py-5 space-y-4">
                @if ($tut->descricao)
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ $tut->descricao }}</p>
                @endif

                <div class="aspect-video bg-slate-900 rounded-lg overflow-hidden">
                    @if ($tut->isUpload())
                        <video src="{{ $tut->videoSrc() }}" controls preload="metadata"
                               class="w-full h-full"></video>
                    @elseif ($tut->embedUrl())
                        <template x-if="aberto === {{ $tut->id }}">
                            <iframe src="{{ $tut->embedUrl() }}"
                                    class="w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        </template>
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center text-slate-300 text-sm">
                            <i class="ri-error-warning-line text-3xl mb-2"></i>
                            <span>Vídeo indisponível.</span>
                            @if ($tut->video_url)
                                <a href="{{ $tut->video_url }}" target="_blank" class="text-indigo-300 hover:underline mt-2 text-xs">
                                    Abrir link original <i class="ri-external-link-line"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm px-6 py-16 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="ri-video-line text-3xl text-slate-400"></i>
            </div>
            <p class="text-slate-500 font-medium mb-1">
                @if ($busca)
                    Nenhum tutorial encontrado para "{{ $busca }}"
                @else
                    Nenhum tutorial disponível ainda
                @endif
            </p>
            <p class="text-xs text-slate-400">
                @if ($busca)
                    Tente buscar por outro termo.
                @else
                    Em breve teremos vídeos ensinando a usar o sistema.
                @endif
            </p>
        </div>
    @endforelse
</div>
@endsection
