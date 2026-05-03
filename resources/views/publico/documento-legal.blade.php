<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documento->titulo }} - FidelizaPro</title>
    <meta name="robots" content="index, follow">
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-6 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">F</div>
                <span class="font-bold text-slate-800">FidelizaPro</span>
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

    <footer class="max-w-3xl mx-auto px-6 py-8 text-center text-xs text-slate-500">
        <p>FidelizaPro &middot; <a href="{{ url('/politica-privacidade') }}" class="hover:underline">Política de privacidade</a> &middot; <a href="{{ url('/termos-de-uso') }}" class="hover:underline">Termos de uso</a></p>
    </footer>
</body>
</html>
