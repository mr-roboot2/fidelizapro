@extends('layouts.super')
@section('title', 'Editar: '.$documento->titulo)
@section('content')
<form method="POST" action="{{ route('super.documentos.update', $documento->slug) }}" class="space-y-4">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <a href="{{ route('super.documentos.index') }}" class="text-sm text-slate-500 hover:text-slate-800">
            <i class="ri-arrow-left-line"></i> Voltar
        </a>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Título</label>
            <input type="text" name="titulo" value="{{ old('titulo', $documento->titulo) }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
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

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <a href="{{ route('super.documentos.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</a>
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-lg font-medium">
                <i class="ri-save-line"></i> Salvar alterações
            </button>
        </div>
    </div>
</form>

<details class="bg-white rounded-xl shadow-sm p-6 mt-4">
    <summary class="cursor-pointer text-sm font-medium text-slate-700">Pré-visualização do conteúdo atual</summary>
    <div class="prose prose-slate max-w-none mt-4 prose-h2:text-lg prose-h2:font-semibold prose-h2:mt-4 prose-p:text-sm">
        {!! str_replace('{DATA}', $documento->updated_at->format('d/m/Y'), $documento->conteudo) !!}
    </div>
</details>

<script src="https://cdn.tailwindcss.com?plugins=typography"></script>
@endsection
