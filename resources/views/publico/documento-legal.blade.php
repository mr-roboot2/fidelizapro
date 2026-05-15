<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documento->titulo }} - {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    <meta name="robots" content="index, follow">
    @if ($sistema?->faviconUrl())
        <link rel="icon" href="{{ $sistema->faviconUrl() }}">
    @endif
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
</head>
@php
    $cor1 = $sistema->cor_primaria ?? '#6366f1';
    $cor2 = $sistema->cor_secundaria ?? '#8b5cf6';
    $nome = $sistema->nome_sistema ?? 'FidelizaPro';
@endphp
<body class="bg-slate-50 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-6 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                @if ($sistema?->logoUrl())
                    <img src="{{ $sistema->logoUrl() }}" alt="{{ $nome }}" class="w-9 h-9 rounded-lg object-contain">
                @else
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">{{ strtoupper(substr($nome, 0, 1)) }}</div>
                @endif
                <span class="font-bold text-slate-800">{{ $nome }}</span>
            </a>
            <a href="javascript:history.back()" class="text-sm text-slate-500 hover:text-slate-800">
                <i class="ri-arrow-left-line"></i> Voltar
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10">
        <div class="bg-white rounded-2xl shadow-sm p-8">
            <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $documento->titulo }}</h1>
            <p class="text-sm text-slate-500 mb-8">Atualizado em {{ $documento->updated_at->format('d/m/Y') }}</p>

            <article class="prose prose-slate max-w-none prose-h2:text-xl prose-h2:font-semibold prose-h2:mt-8 prose-h2:mb-3 prose-p:text-sm prose-li:text-sm">
                {!! str_replace('{DATA}', $documento->updated_at->format('d/m/Y'), $documento->conteudo) !!}
            </article>
        </div>

        <div class="text-center mt-6">
            <a href="/" class="text-sm text-indigo-600 hover:underline">
                <i class="ri-arrow-left-line"></i> Voltar à página inicial
            </a>
        </div>
    </main>

    <footer class="max-w-3xl mx-auto px-6 py-8 text-center text-xs text-slate-500 space-y-2">
        @if (!empty($sistema?->rodape_html))
            <div>{!! $sistema->rodape_html !!}</div>
        @endif
        <p>
            {{ $nome }}
            @if ($docPriv = \App\Models\DocumentoLegal::porSlug('politica-privacidade'))
                &middot; <a href="{{ url('/'.$docPriv->slug) }}" class="hover:underline">{{ $docPriv->titulo }}</a>
            @endif
            @if ($docTermos = \App\Models\DocumentoLegal::porSlug('termos-de-uso'))
                &middot; <a href="{{ url('/'.$docTermos->slug) }}" class="hover:underline">{{ $docTermos->titulo }}</a>
            @endif
        </p>
    </footer>
</body>
</html>
