<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') - {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <style>
        [x-cloak]{display:none!important}
        /* Scrollbar discreto no sidebar escuro */
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.15) transparent; }
        .sidebar-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 h-screen flex overflow-hidden">
<aside x-data="{open:true}" :class="open?'w-64':'w-20'" class="bg-slate-900 text-slate-200 transition-all duration-200 flex flex-col h-screen sticky top-0 shrink-0">
    <div class="p-4 flex items-center justify-between border-b border-slate-800">
        @php $nomeSistema = $sistema->nome_sistema ?? 'FidelizaPro'; @endphp
        <div class="flex items-center gap-2" x-show="open">
            @if (!empty($sistema) && $sistema->logoUrl())
                <img src="{{ $sistema->logoUrl() }}" alt="{{ $nomeSistema }}" class="w-8 h-8 rounded-lg object-contain bg-white/10">
            @else
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold"
                     style="background:{{ $empresaAtiva->cor_primaria ?? '#6366f1' }}">{{ mb_strtoupper(mb_substr($nomeSistema, 0, 1)) }}</div>
            @endif
            <span class="font-bold">{{ $nomeSistema }}</span>
        </div>
        <button @click="open=!open" class="text-slate-400 hover:text-white">
            <i class="ri-menu-line text-xl"></i>
        </button>
    </div>
    <nav class="flex-1 py-4 space-y-1 overflow-y-auto sidebar-scroll">
        @php
            // 4º campo: módulo necessário (null = sempre disponível)
            $itens = [
                ['admin.dashboard', 'ri-dashboard-line', 'Dashboard', null],
                ['admin.caixa.index', 'ri-cash-line', 'Caixa rápido', null],
                ['admin.clientes.index', 'ri-user-line', 'Clientes', null],
                ['admin.compras.index', 'ri-shopping-cart-line', 'Compras', null],
                ['admin.regras.index', 'ri-stack-line', 'Regras de pontuação', null],
                ['admin.recompensas.index', 'ri-gift-line', 'Recompensas', null],
                ['admin.roleta.index', 'ri-bubble-chart-line', 'Roleta da sorte', 'roleta'],
                ['admin.sorteios.index', 'ri-ticket-2-line', 'Sorteios', 'sorteio'],
                ['admin.resgates.index', 'ri-coupon-line', 'Resgates', null],
                ['admin.transacoes.index', 'ri-exchange-line', 'Transações', null],
                ['admin.cashback.index', 'ri-money-dollar-circle-line', 'Cashback', null],
                ['admin.avaliacoes.index', 'ri-star-line', 'Avaliações', null],
                ['admin.parceiros.index', 'ri-shake-hands-line', 'Parceiros', 'parceiros'],
                ['admin.ai-growth.index', 'ri-magic-line', 'AI Growth', 'ai_growth'],
                ['admin.atividade.suspeita', 'ri-shield-keyhole-line', 'Antifraude', 'antifraude'],
                ['admin.meu-plano.index', 'ri-vip-crown-line', 'Meu plano', null],
                ['admin.setup.index', 'ri-rocket-2-line', 'Setup inicial', '__setup__'],
                ['admin.importacao.index', 'ri-plug-line', 'Importação / PDV', null],
                ['admin.configuracoes.edit', 'ri-settings-3-line', 'Configurações', null],
            ];
        @endphp
        @foreach ($itens as [$rota, $icone, $rotulo, $modulo])
            @if ($modulo === '__setup__')
                @if (!isset($empresaAtiva) || $empresaAtiva->setup_concluido) @continue @endif
            @elseif ($modulo && isset($empresaAtiva) && !$empresaAtiva->temModulo($modulo)) @continue @endif
            @php $ativo = request()->routeIs(str_replace('.index','.*',$rota)) || request()->routeIs($rota); @endphp
            <a href="{{ route($rota) }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg transition
                      {{ $ativo ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800' }}">
                <i class="{{ $icone }} text-lg shrink-0"></i>
                <span x-show="open" class="text-sm">{{ $rotulo }}</span>
            </a>
        @endforeach
    </nav>
    <div class="p-3 border-t border-slate-800 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
            {{ strtoupper(substr($userAtivo->name ?? 'U', 0, 1)) }}
        </div>
        <div x-show="open" class="flex-1 min-w-0">
            <div class="text-sm font-medium truncate">{{ $userAtivo->name ?? '' }}</div>
            <div class="text-xs text-slate-400 truncate">{{ $empresaAtiva->nome ?? '' }}</div>
        </div>
        <form action="{{ route('admin.logout') }}" method="POST" x-show="open">
            @csrf
            <button class="text-slate-400 hover:text-white" title="Sair">
                <i class="ri-logout-box-line"></i>
            </button>
        </form>
    </div>
</aside>

<main class="flex-1 flex flex-col overflow-y-auto h-screen">
    @include('admin._partials.banner_inadimplencia')
    @if (isset($empresaAtiva) && !$empresaAtiva->setup_concluido && !request()->routeIs('admin.setup.*'))
        <a href="{{ route('admin.setup.index') }}"
           class="bg-gradient-to-r from-indigo-600 via-purple-600 to-rose-500 hover:brightness-110 text-white px-4 py-2 text-sm flex items-center justify-between transition">
            <span class="flex items-center gap-2">
                <i class="ri-rocket-2-line text-base"></i>
                <strong>Sua loja ainda não está 100% configurada</strong>
                <span class="hidden sm:inline opacity-80">— siga o setup inicial pra começar a atender clientes.</span>
            </span>
            <span class="flex items-center gap-1 bg-white/20 px-2 py-0.5 rounded text-xs font-medium">
                Configurar agora <i class="ri-arrow-right-line"></i>
            </span>
        </a>
    @endif
    @if (session('impersonate_origem_id'))
        <div class="bg-amber-500 text-white px-4 py-2 text-sm flex items-center justify-between">
            <span><i class="ri-spy-line"></i> Você está acessando como <strong>{{ $userAtivo->name ?? '' }}</strong> ({{ $empresaAtiva->nome ?? '' }})</span>
            <form action="{{ route('super.impersonate.sair') }}" method="POST">
                @csrf
                <button class="bg-white/20 px-3 py-1 rounded text-xs hover:bg-white/30">
                    <i class="ri-arrow-go-back-line"></i> Voltar ao super admin
                </button>
            </form>
        </div>
    @endif
    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slate-700">@yield('title', 'Painel')</h1>
        <div class="flex items-center gap-4">
            <a href="{{ url('/loja/') }}" target="_blank" class="text-sm text-emerald-600 hover:underline">
                <i class="ri-qr-scan-2-line"></i> PWA da Loja
            </a>
            <a href="{{ isset($empresaAtiva->slug) ? url('/app/'.$empresaAtiva->slug.'/') : '/app/' }}" target="_blank" class="text-sm text-indigo-600 hover:underline">
                <i class="ri-smartphone-line"></i> Ver portal cliente
            </a>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto p-6">
        @if (session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 px-4 py-3 mb-4 rounded">
                <i class="ri-check-line"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 px-4 py-3 mb-4 rounded">
                <i class="ri-error-warning-line"></i> {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 px-4 py-3 mb-4 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $erro) <li>{{ $erro }}</li> @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </div>
</main>
</body>
</html>
