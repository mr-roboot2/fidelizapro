@extends('layouts.super')
@section('title', $modo === 'criar' ? 'Novo tutorial' : 'Editar: '.$tutorial->titulo)
@section('content')
<form method="POST"
      action="{{ $modo === 'criar' ? route('super.tutoriais.store') : route('super.tutoriais.update', $tutorial) }}"
      enctype="multipart/form-data"
      class="space-y-4"
      x-data="{ tipo: '{{ old('tipo_video', $tutorial->tipo_video ?? 'url') }}' }">
    @csrf
    @if ($modo === 'editar') @method('PUT') @endif

    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('super.tutoriais.index') }}" class="text-sm text-slate-500 hover:text-slate-800">
                <i class="ri-arrow-left-line"></i> Voltar
            </a>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Título</label>
            <input type="text" name="titulo" value="{{ old('titulo', $tutorial->titulo) }}" required
                   placeholder="Ex: Como cadastrar um cliente no caixa rápido"
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Descrição <span class="text-slate-400 font-normal">(opcional)</span></label>
            <textarea name="descricao" rows="3"
                      placeholder="Resumo curto que aparece embaixo do título na central de ajuda."
                      class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none text-sm">{{ old('descricao', $tutorial->descricao) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Origem do vídeo</label>
            <div class="grid grid-cols-2 gap-2">
                <label class="border rounded-lg p-3 cursor-pointer flex items-start gap-2"
                       :class="tipo === 'url' ? 'border-rose-500 bg-rose-50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="tipo_video" value="url" x-model="tipo" class="mt-1">
                    <div>
                        <p class="text-sm font-medium">URL YouTube/Vimeo</p>
                        <p class="text-xs text-slate-500">Cole o link do vídeo. Mais leve e rápido.</p>
                    </div>
                </label>
                <label class="border rounded-lg p-3 cursor-pointer flex items-start gap-2"
                       :class="tipo === 'upload' ? 'border-rose-500 bg-rose-50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="tipo_video" value="upload" x-model="tipo" class="mt-1">
                    <div>
                        <p class="text-sm font-medium">Upload de arquivo</p>
                        <p class="text-xs text-slate-500">MP4/WebM/MOV até 200 MB.</p>
                    </div>
                </label>
            </div>
        </div>

        <div x-show="tipo === 'url'" x-cloak>
            <label class="block text-sm font-medium text-slate-700 mb-1">URL do vídeo</label>
            <input type="url" name="video_url" value="{{ old('video_url', $tutorial->video_url) }}"
                   placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/..."
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none font-mono text-xs">
            <p class="text-xs text-slate-500 mt-1">
                Aceita YouTube (watch, youtu.be, embed, shorts) e Vimeo. O sistema converte automaticamente pra embed.
            </p>
        </div>

        <div x-show="tipo === 'upload'" x-cloak>
            <label class="block text-sm font-medium text-slate-700 mb-1">Arquivo de vídeo</label>
            @if ($tutorial->video_arquivo)
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm">
                        <i class="ri-film-line text-slate-500"></i>
                        <span class="text-slate-700">Arquivo atual:</span>
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tutorial->video_arquivo) }}"
                           target="_blank" class="text-rose-600 hover:underline font-mono text-xs">
                            {{ basename($tutorial->video_arquivo) }}
                        </a>
                    </div>
                </div>
            @endif
            <input type="file" name="video_arquivo" accept="video/mp4,video/webm,video/ogg,video/quicktime"
                   class="w-full text-sm text-slate-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-rose-600 file:text-white file:font-medium file:cursor-pointer hover:file:bg-rose-700">
            <p class="text-xs text-slate-500 mt-1">
                @if ($tutorial->video_arquivo)
                    Envie um novo arquivo só se quiser substituir.
                @else
                    Formatos aceitos: MP4, WebM, OGG, MOV. Tamanho máximo 200 MB.
                @endif
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Duração <span class="text-slate-400 font-normal">(opcional)</span></label>
                <input type="text" name="duracao" value="{{ old('duracao', $tutorial->duracao) }}"
                       placeholder="Ex: 4:32"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ordem</label>
                <input type="number" name="ordem" value="{{ old('ordem', $tutorial->ordem) }}" min="0"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Menor número aparece primeiro. Pode arrastar na lista também.</p>
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="publicado" value="0">
                <input type="checkbox" name="publicado" value="1"
                       {{ old('publicado', $tutorial->publicado ?? true) ? 'checked' : '' }}
                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                Publicado (visível pras empresas)
            </label>
        </div>

        <div class="flex justify-between items-center gap-2 pt-4 border-t border-slate-100">
            @if ($modo === 'editar')
                <form method="POST" action="{{ route('super.tutoriais.destroy', $tutorial) }}"
                      onsubmit="return confirm('Excluir o tutorial &quot;{{ $tutorial->titulo }}&quot;? Esta ação não pode ser desfeita.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-rose-600 hover:underline">
                        <i class="ri-delete-bin-line"></i> Excluir tutorial
                    </button>
                </form>
            @else <span></span> @endif
            <div class="flex gap-2">
                <a href="{{ route('super.tutoriais.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</a>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-lg font-medium">
                    <i class="ri-save-line"></i> {{ $modo === 'criar' ? 'Criar tutorial' : 'Salvar alterações' }}
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
