@extends('layouts.admin')
@section('title', 'Meu plano')
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">
    <!-- Plano atual + consumo -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4">Plano atual</h2>

        @if ($empresa->plano)
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl p-5 mb-6">
                <p class="text-white/80 text-sm">Você está no plano</p>
                <p class="text-2xl font-bold">{{ $empresa->plano->nome }}</p>
                <p class="text-white/80 text-sm mt-2">
                    R$ {{ number_format($empresa->plano->preco_mensal, 2, ',', '.') }} / mês
                </p>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <p class="font-semibold text-amber-800"><i class="ri-information-line"></i> Sem plano atribuído</p>
                <p class="text-sm text-amber-700">Sua empresa está em modo livre (sem limites). Entre em contato com o super admin para definir um plano.</p>
            </div>
        @endif

        <h3 class="font-semibold mb-3">Consumo atual</h3>
        <div class="space-y-3">
            @foreach ([
                'clientes' => 'Clientes cadastrados',
                'compras_mes' => 'Compras este mês',
                'recompensas' => 'Recompensas ativas',
                'parceiros' => 'Parceiros ativos',
                'users' => 'Usuários administradores',
                'campanhas_mes' => 'Campanhas este mês',
            ] as $k => $rotulo)
                @php $c = $consumo[$k]; $pct = $c['percentual']; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $rotulo }}</span>
                        <span class="font-semibold">
                            {{ $c['atual'] }}
                            @if ($c['limite']) / {{ $c['limite'] }}
                            @else <span class="text-emerald-600">(ilimitado)</span>
                            @endif
                        </span>
                    </div>
                    @if ($c['limite'])
                        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div @class([
                                'h-full transition-all',
                                'bg-emerald-500' => $pct < 70,
                                'bg-amber-500' => $pct >= 70 && $pct < 90,
                                'bg-rose-500' => $pct >= 90,
                            ]) style="width: {{ min($pct, 100) }}%"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Planos disponíveis -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4">Planos disponíveis</h2>
        <div class="space-y-3">
            @forelse ($planosDisponiveis as $p)
                <div @class([
                    'border-2 rounded-xl p-4',
                    'border-indigo-500 bg-indigo-50' => $empresa->plano_id === $p->id,
                    'border-slate-200' => $empresa->plano_id !== $p->id,
                ])>
                    <div class="flex items-center justify-between">
                        <p class="font-semibold">{{ $p->nome }}</p>
                        @if ($empresa->plano_id === $p->id)
                            <span class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded-full">Atual</span>
                        @endif
                    </div>
                    <p class="text-2xl font-bold mt-1">R$ {{ number_format($p->preco_mensal, 2, ',', '.') }}<span class="text-xs text-slate-500 font-normal">/mês</span></p>
                    <ul class="text-xs text-slate-600 mt-2 space-y-1">
                        @if ($p->limite_clientes)<li>✓ Até {{ number_format($p->limite_clientes, 0, ',', '.') }} clientes</li>@else<li>✓ Clientes ilimitados</li>@endif
                        @if ($p->limite_compras_mes)<li>✓ Até {{ number_format($p->limite_compras_mes, 0, ',', '.') }} compras/mês</li>@else<li>✓ Compras ilimitadas</li>@endif
                        @if ($p->automacoes_disponivel)<li>✓ Automações WhatsApp</li>@endif
                        @if ($p->parceiros_disponivel)<li>✓ Área de parceiros</li>@endif
                        @if ($p->white_label_disponivel)<li>✓ White label PWA</li>@endif
                    </ul>
                </div>
            @empty
                <p class="text-sm text-slate-400">Sem planos cadastrados.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
