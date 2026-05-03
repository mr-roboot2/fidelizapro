@extends('layouts.super')
@section('title', 'Documentos legais')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="px-6 py-4 border-b border-slate-200">
        <h2 class="font-semibold">Política de privacidade e termos de uso</h2>
        <p class="text-xs text-slate-500 mt-0.5">Editáveis pelo super admin. As páginas públicas ficam disponíveis para todos os clientes.</p>
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
                        <a href="{{ url('/'.($doc->slug === 'privacidade' ? 'politica-privacidade' : 'termos-de-uso')) }}"
                           target="_blank" class="text-indigo-600 hover:underline">
                            /{{ $doc->slug === 'privacidade' ? 'politica-privacidade' : 'termos-de-uso' }}
                        </a>
                        &middot; Última atualização: {{ $doc->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <a href="{{ route('super.documentos.edit', $doc->slug) }}"
                   class="text-sm bg-rose-600 text-white px-4 py-2 rounded-lg hover:bg-rose-700">
                    <i class="ri-edit-line"></i> Editar
                </a>
            </li>
        @empty
            <li class="px-6 py-10 text-center text-slate-400">
                Nenhum documento cadastrado. Rode o seeder <code class="bg-slate-100 px-1 rounded">php artisan db:seed --class=DocumentoLegalSeeder</code>.
            </li>
        @endforelse
    </ul>
</div>
@endsection
