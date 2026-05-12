@extends('layouts.admin')
@section('title', 'Setup inicial')
@section('content')

@if (session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">
        <i class="ri-check-line"></i> {{ session('success') }}
    </div>
@endif

@php
    $pct = $resumo['percentual'];
    $cor = $pct === 100 ? 'emerald' : ($pct >= 60 ? 'indigo' : 'amber');
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-rose-500 text-white rounded-2xl p-7 relative overflow-hidden shadow-sm">
        <div class="absolute -right-10 -bottom-10 opacity-15 text-[12rem] leading-none"><i class="ri-rocket-2-line"></i></div>
        <div class="relative">
            <div class="flex items-center gap-2 text-white/80 text-xs uppercase tracking-wider mb-1">
                <i class="ri-magic-line"></i> Setup inicial
            </div>
            <h1 class="text-2xl font-bold mb-1">Bem-vindo ao {{ config('app.name') }}, {{ $empresa->nome }}!</h1>
            <p class="text-white/85 text-sm mb-5">Siga estes passos pra deixar sua loja pronta pra atender clientes.</p>

            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="flex justify-between text-xs mb-1 text-white/80">
                        <span>{{ $resumo['concluidos'] }} de {{ $resumo['total'] }} concluídos</span>
                        <span class="font-semibold">{{ $pct }}%</span>
                    </div>
                    <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.setup.pular') }}"
                      onsubmit="return confirm('Pular o setup? Você pode voltar aqui depois em /admin/setup');">
                    @csrf
                    <button class="px-4 py-2 bg-white/15 hover:bg-white/25 backdrop-blur rounded-lg text-sm font-medium transition">
                        <i class="ri-skip-forward-line"></i> Pular configuração
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($resumo['obrigatorios_ok'])
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
            <i class="ri-checkbox-circle-fill text-lg"></i>
            <div>
                <strong>Tudo pronto!</strong> Os passos obrigatórios estão completos — sua loja já está operacional.
                @if ($pct < 100)
                    Vá adiantando os passos opcionais quando puder.
                @endif
            </div>
        </div>
    @else
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm flex items-center gap-2">
            <i class="ri-error-warning-fill text-lg"></i>
            <div>
                Faltam <strong>{{ $resumo['obrigatorios_total'] - $resumo['obrigatorios_concluidos'] }}</strong>
                {{ Str::plural('passo obrigatório', $resumo['obrigatorios_total'] - $resumo['obrigatorios_concluidos']) }}
                pra sua loja funcionar 100%.
            </div>
        </div>
    @endif

    {{-- LISTA DE PASSOS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($resumo['passos'] as $i => $passo)
            <div class="bg-white border {{ $passo['concluido'] ? 'border-emerald-200' : 'border-slate-200' }} rounded-xl p-5 hover:shadow-md transition relative">
                @if ($passo['concluido'])
                    <div class="absolute top-3 right-3 w-7 h-7 bg-emerald-500 rounded-full flex items-center justify-center text-white">
                        <i class="ri-check-line"></i>
                    </div>
                @endif

                <div class="flex items-start gap-3 mb-3">
                    <div class="w-11 h-11 rounded-lg flex items-center justify-center text-xl shrink-0
                                {{ $passo['concluido'] ? 'bg-emerald-100 text-emerald-600' : 'bg-indigo-100 text-indigo-600' }}">
                        <i class="{{ $passo['icone'] }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                            <span class="text-xs font-mono text-slate-400">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="font-semibold text-slate-900">{{ $passo['titulo'] }}</h3>
                            @if ($passo['obrigatorio'])
                                <span class="text-[10px] uppercase tracking-wider bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-semibold">Obrigatório</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-600">{{ $passo['descricao'] }}</p>
                    </div>
                </div>

                <a href="{{ route($passo['rota_acao']) }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium
                          {{ $passo['concluido'] ? 'text-emerald-600 hover:text-emerald-700' : 'text-indigo-600 hover:text-indigo-700' }}">
                    {{ $passo['concluido'] ? 'Revisar' : $passo['label_acao'] }}
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        @endforeach
    </div>

    {{-- AÇÃO FINAL --}}
    @if ($empresa->setup_concluido && $pct < 100)
        <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between flex-wrap gap-3">
            <div class="text-sm text-slate-600">
                Você pulou o setup. Quer voltar e exibir o banner de progresso de novo?
            </div>
            <form method="POST" action="{{ route('admin.setup.reabrir') }}">
                @csrf
                <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-medium text-slate-700">
                    <i class="ri-refresh-line"></i> Reabrir wizard
                </button>
            </form>
        </div>
    @endif

</div>

@endsection
