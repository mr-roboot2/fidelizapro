@extends('layouts.super')
@section('title', 'Documentos legais')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-semibold">Páginas legais e institucionais</h2>
            <p class="text-xs text-slate-500 mt-0.5">Política de privacidade, termos de uso, ou qualquer outra página estática que precise ser pública.</p>
        </div>
        <a href="{{ route('super.documentos.create') }}"
           class="bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2 rounded-lg font-medium">
            <i class="ri-add-line"></i> Nova página
        </a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse ($documentos as $doc)
            <li class="px-6 py-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center">
                    <i class="ri-file-text-line text-rose-600 text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800">{{ $doc->titulo }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        URL pública:
                        <a href="{{ url('/'.$doc->slug) }}" target="_blank" class="text-indigo-600 hover:underline font-mono">/{{ $doc->slug }}</a>
                        &middot; Última atualização: {{ $doc->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <a href="{{ route('super.documentos.edit', $doc->slug) }}"
                   class="text-sm bg-rose-600 text-white px-4 py-2 rounded-lg hover:bg-rose-700">
                    <i class="ri-edit-line"></i> Editar
                </a>
            </li>
        @empty
            <li class="px-6 py-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="ri-file-text-line text-3xl text-slate-400"></i>
                </div>
                <p class="text-slate-500 font-medium mb-1">Nenhuma página cadastrada</p>
                <p class="text-xs text-slate-400 mb-4">Crie a primeira clicando em "Nova página" acima.</p>
            </li>
        @endforelse
    </ul>
</div>
@endsection
