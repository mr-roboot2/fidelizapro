<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $config->cor_primaria }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $config->nome_sistema }}">
    <link rel="manifest" href="{{ url('/app/manifest.json') }}">
    @if ($config->logo)
        <link rel="apple-touch-icon" href="{{ $config->logoUrl() }}">
        <link rel="icon" href="{{ $config->logoUrl() }}">
    @else
        <link rel="apple-touch-icon" href="{{ asset('app/icons/icon.svg') }}">
        <link rel="icon" href="{{ asset('app/icons/icon.svg') }}" type="image/svg+xml">
    @endif
    <title>{{ $config->nome_sistema }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@@4.2.0/fonts/remixicon.css">
    <link rel="stylesheet" href="{{ asset('app/style.css') }}?v={{ @filemtime(public_path('app/style.css')) }}">
    <style>
        :root {
            --cor-primaria: {{ $config->cor_primaria }};
            --cor-secundaria: {{ $config->cor_secundaria }};
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

<div id="app" class="max-w-md mx-auto min-h-screen flex flex-col bg-white shadow-xl">
    <div id="screen-container" class="flex-1 flex flex-col"></div>

    <nav id="bottom-nav" class="hidden border-t border-slate-200 bg-white sticky bottom-0 grid grid-cols-5 text-xs">
        <button onclick="showScreen('home')" class="nav-btn py-3 flex flex-col items-center gap-1 text-slate-500">
            <i class="ri-home-5-line text-xl"></i><span>Início</span>
        </button>
        <button onclick="showScreen('compras')" class="nav-btn py-3 flex flex-col items-center gap-1 text-slate-500">
            <i class="ri-shopping-bag-line text-xl"></i><span>Compras</span>
        </button>
        <button onclick="showScreen('catalogo')" class="nav-btn py-3 flex flex-col items-center gap-1 text-slate-500">
            <i class="ri-gift-line text-xl"></i><span>Prêmios</span>
        </button>
        <button onclick="showScreen('qrcode')" class="nav-btn py-3 flex flex-col items-center gap-1 text-slate-500">
            <i class="ri-qr-code-line text-xl"></i><span>QR Code</span>
        </button>
        <button onclick="showScreen('perfil')" class="nav-btn py-3 flex flex-col items-center gap-1 text-slate-500">
            <i class="ri-user-line text-xl"></i><span>Perfil</span>
        </button>
    </nav>
</div>

<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden bg-slate-900 text-white px-4 py-2 rounded-full text-sm shadow-lg"></div>
<button id="install-btn" class="hidden fixed bottom-20 right-4 bg-indigo-600 text-white rounded-full px-4 py-3 shadow-lg z-40">
    <i class="ri-download-cloud-line"></i> Instalar app
</button>

<script src="{{ asset('app/app.js') }}?v={{ @filemtime(public_path('app/app.js')) }}"></script>
</body>
</html>
