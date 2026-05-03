@extends('layouts.super')
@section('title', $modo === 'criar' ? 'Nova página' : 'Editar: '.$documento->titulo)
@section('content')
<form method="POST"
      action="{{ $modo === 'criar' ? route('super.documentos.store') : route('super.documentos.update', $documento->slug) }}"
      class="space-y-4">
    @csrf
    @if ($modo === 'editar') @method('PUT') @endif

    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('super.documentos.index') }}" class="text-sm text-slate-500 hover:text-slate-800">
                <i class="ri-arrow-left-line"></i> Voltar
            </a>
            @if ($modo === 'editar')
                <a href="{{ url('/'.$documento->slug) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg">
                    <i class="ri-external-link-line"></i> Ver página pública
                </a>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Título</label>
            <input type="text" name="titulo" value="{{ old('titulo', $documento->titulo) }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">URL pública (slug)</label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500 whitespace-nowrap">{{ url('/') }}/</span>
                <input type="text" name="slug" value="{{ old('slug', $documento->slug) }}" required
                       pattern="^[a-z][a-z0-9-]*$"
                       placeholder="ex: politica-privacidade"
                       class="flex-1 px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none font-mono text-sm">
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Apenas letras minúsculas, números e hífens. Reservados: <code>admin</code>, <code>super</code>, <code>api</code>, <code>app</code>, <code>install</code>, <code>login</code>, etc.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Conteúdo (HTML)</label>
            <textarea name="conteudo" rows="22" required
                      class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none font-mono text-xs">{{ old('conteudo', $documento->conteudo) }}</textarea>
            <p class="text-xs text-slate-500 mt-1">
                Aceita HTML básico: <code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;/&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;a href&gt;</code>.
                Use <code class="bg-slate-100 px-1 rounded">{DATA}</code> onde quiser que apareça a data atual.
            </p>
        </div>

        <div class="flex justify-between items-center gap-2 pt-4 border-t border-slate-100">
            @if ($modo === 'editar')
                <form method="POST" action="{{ route('super.documentos.destroy', $documento->slug) }}"
                      onsubmit="return confirm('Excluir a página &quot;{{ $documento->titulo }}&quot;? Esta ação não pode ser desfeita.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-rose-600 hover:underline">
                        <i class="ri-delete-bin-line"></i> Excluir página
                    </button>
                </form>
            @else <span></span> @endif
            <div class="flex gap-2">
                <a href="{{ route('super.documentos.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</a>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-lg font-medium">
                    <i class="ri-save-line"></i> {{ $modo === 'criar' ? 'Criar página' : 'Salvar alterações' }}
                </button>
            </div>
        </div>
    </div>
</form>

@if ($modo === 'editar')
<details class="bg-white rounded-xl shadow-sm p-6 mt-4">
    <summary class="cursor-pointer text-sm font-medium text-slate-700">Pré-visualização do conteúdo atual</summary>
    <div class="prose prose-slate max-w-none mt-4 prose-h2:text-lg prose-h2:font-semibold prose-h2:mt-4 prose-p:text-sm">
        {!! str_replace('{DATA}', $documento->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y'), $documento->conteudo) !!}
    </div>
</details>
<script src="https://cdn.tailwindcss.com?plugins=typography"></script>
@endif
@endsection
