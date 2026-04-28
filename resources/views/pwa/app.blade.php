<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $empresa->cor_primaria }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $empresa->nome }}">
    <link rel="manifest" href="manifest.json">
    @if ($empresa->logo)
        <link rel="apple-touch-icon" href="{{ asset('storage/'.$empresa->logo) }}">
        <link rel="icon" href="{{ asset('storage/'.$empresa->logo) }}">
    @else
        <link rel="apple-touch-icon" href="{{ asset('app/icons/icon.svg') }}">
        <link rel="icon" href="{{ asset('app/icons/icon.svg') }}" type="image/svg+xml">
    @endif
    <title>{{ $empresa->nome }} — Fidelidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link rel="stylesheet" href="{{ asset('app/style.css') }}">
    <style>
        :root {
            --cor-primaria: {{ $empresa->cor_primaria }};
            --cor-secundaria: {{ $empresa->cor_secundaria }};
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

<script>
// White label: empresa pré-carregada do servidor
window.PRELOAD_EMPRESA = {!! json_encode([
    'id' => $empresa->id,
    'slug' => $empresa->slug,
    'nome' => $empresa->nome,
    'logo' => $empresa->logo ? asset('storage/'.$empresa->logo) : null,
    'cor_primaria' => $empresa->cor_primaria,
    'cor_secundaria' => $empresa->cor_secundaria,
    'pontos_por_real' => (float) $empresa->pontos_por_real,
    'cashback_percentual' => (float) $empresa->cashback_percentual,
]) !!};
window.WHITELABEL_SW = '{{ url("/app/{$empresa->slug}/sw.js") }}';
</script>
<script src="{{ asset('app/app.js') }}"></script>
</body>
</html>
