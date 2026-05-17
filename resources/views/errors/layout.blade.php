{{--
    Layout base pros 404/419/500/503 customizados — Whoops genérico não
    serve UX brasileira. Mantém marca/cores do sistema mesmo quando
    o app está fora do ar.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('codigo') · {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    @if ($sistema?->faviconUrl())
        <link rel="icon" href="{{ $sistema->faviconUrl() }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ "https://cdn.jsdelivr.net/npm/remixicon" }}@4.2.0/fonts/remixicon.css"
          integrity="sha384-6FSSi597BTd6QcnsBNoLclRKxTOyyYqkaucRjFgCNr8wHVCp0COLClSPY4Vy/bjh"
          crossorigin="anonymous">
</head>
@php
    $cor1 = $sistema->cor_primaria ?? '#6366f1';
    $cor2 = $sistema->cor_secundaria ?? '#8b5cf6';
@endphp
<body class="min-h-screen flex items-center justify-center p-4 text-white" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center text-slate-800">
        <div class="w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
            <i class="@yield('icone', 'ri-error-warning-line') text-4xl text-white"></i>
        </div>
        <p class="text-6xl font-bold text-slate-400 mb-2">@yield('codigo')</p>
        <h1 class="text-xl font-bold text-slate-800 mb-2">@yield('titulo', 'Algo deu errado')</h1>
        <p class="text-slate-500 text-sm mb-6">@yield('mensagem')</p>
        <a href="{{ url('/') }}" class="inline-block px-6 py-2.5 rounded-lg font-semibold text-white" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
            <i class="ri-home-line"></i> Voltar ao início
        </a>
    </div>
</body>
</html>
