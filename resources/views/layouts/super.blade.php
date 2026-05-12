<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') - {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex">
<aside x-data="{open:true}" :class="open?'w-64':'w-20'"
       class="bg-gradient-to-b from-slate-900 via-slate-900 to-rose-950 text-slate-200 transition-all duration-200 flex flex-col">
    <div class="p-4 flex items-center justify-between border-b border-slate-800">
        <div class="flex items-center gap-2" x-show="open">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold bg-gradient-to-br from-rose-500 to-orange-500">S</div>
            <div>
                <span class="font-bold text-sm block leading-tight">Super Admin</span>
                <span class="text-xs text-slate-400">{{ $sistema->nome_sistema ?? 'FidelizaPro' }}</span>
            </div>
        </div>
        <button @click="open=!open" class="text-slate-400 hover:text-white">
            <i class="ri-menu-line text-xl"></i>
        </button>
    </div>
    <nav class="flex-1 py-4 space-y-1 overflow-y-auto">
        @php
            $itens = [
                ['super.dashboard', 'ri-dashboard-line', 'Dashboard global'],
                ['super.empresas.index', 'ri-building-line', 'Empresas'],
                ['super.users.index', 'ri-user-settings-line', 'Usuários'],
                ['super.assinaturas.index', 'ri-bank-card-line', 'Assinaturas'],
                ['super.planos.index', 'ri-stack-line', 'Planos'],
                ['super.auditoria.index', 'ri-history-line', 'Auditoria'],
                ['super.documentos.index', 'ri-file-text-line', 'Documentos legais'],
                ['super.whatsapp.edit', 'ri-whatsapp-fill', 'WhatsApp'],
                ['super.whatsapp-templates.index', 'ri-message-3-line', 'Templates WhatsApp'],
                ['super.automacoes.index', 'ri-magic-line', 'Automações'],
                ['super.campanhas.index', 'ri-megaphone-line', 'Campanhas'],
                ['super.whatsapp-logs.index', 'ri-history-line', 'Logs WhatsApp'],
                ['super.cron.index', 'ri-time-line', 'Tarefas (cron)'],
                ['super.configuracoes.edit', 'ri-settings-3-line', 'Configurações'],
            ];
        @endphp
        @foreach ($itens as [$rota, $icone, $rotulo])
            @php $ativo = request()->routeIs(str_replace('.index','.*',$rota)) || request()->routeIs($rota); @endphp
            <a href="{{ route($rota) }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg transition
                      {{ $ativo ? 'bg-rose-600 text-white' : 'hover:bg-slate-800' }}">
                <i class="{{ $icone }} text-lg shrink-0"></i>
                <span x-show="open" class="text-sm">{{ $rotulo }}</span>
            </a>
        @endforeach

        <div class="px-4 pt-4 mt-4 border-t border-slate-800" x-show="open">
            <p class="text-xs uppercase text-slate-500 px-2 mb-2">Painéis das empresas</p>
        </div>
        @foreach (App\Models\Empresa::orderBy('nome')->get() as $emp)
            <form action="{{ route('super.impersonate.entrar', $emp) }}" method="POST" class="mx-2">
                @csrf
                <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-800 text-left">
                    <div class="w-6 h-6 rounded shrink-0" style="background:{{ $emp->cor_primaria }}"></div>
                    <span x-show="open" class="text-xs truncate">{{ $emp->nome }}</span>
                </button>
            </form>
        @endforeach
    </nav>
    <div class="p-3 border-t border-slate-800 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-rose-500 flex items-center justify-center text-white font-bold">
            {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
        </div>
        <div x-show="open" class="flex-1 min-w-0">
            <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
            <div class="text-xs text-slate-400 truncate">Super Admin</div>
        </div>
        <form action="{{ route('admin.logout') }}" method="POST" x-show="open">
            @csrf
            <button class="text-slate-400 hover:text-white" title="Sair">
                <i class="ri-logout-box-line"></i>
            </button>
        </form>
    </div>
</aside>

<main class="flex-1 flex flex-col overflow-hidden">
    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-700">@yield('title', 'Super Admin')</h1>
            <p class="text-xs text-rose-600"><i class="ri-shield-star-line"></i> Modo Super Admin — controle total do sistema</p>
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
