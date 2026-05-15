@extends('layouts.admin')
@section('title', 'Meu plano')
@section('content')

@if (session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">
        <i class="ri-check-line"></i> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg">
        <i class="ri-error-warning-line"></i> {{ session('error') }}
    </div>
@endif

@php
    $planoAtual = $assinatura?->plano ?? $empresa->plano;
    $statusInad = $empresa->statusInadimplencia();
    $cobrancaPendente = $cobrancas->where('status', 'pendente')->first();
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-7xl">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white rounded-xl shadow-sm p-6">
            @if ($planoAtual)
                <div class="bg-gradient-to-br from-indigo-500 via-purple-500 to-rose-500 text-white rounded-2xl p-6 mb-5 relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 opacity-15 text-9xl"><i class="ri-vip-crown-fill"></i></div>
                    <div class="relative">
                        <p class="text-white/80 text-xs uppercase tracking-wider">Plano atual</p>
                        <p class="text-3xl font-bold mt-1">{{ $planoAtual->nome }}</p>
                        <p class="text-white/90 text-sm mt-1">R$ {{ number_format($planoAtual->preco_mensal, 2, ',', '.') }} / mês</p>
                        @if ($assinatura && $assinatura->proximo_vencimento)
                            <p class="text-white/80 text-xs mt-3">
                                <i class="ri-calendar-event-line"></i>
                                Próximo vencimento: <strong>{{ $assinatura->proximo_vencimento->format('d/m/Y') }}</strong>
                            </p>
                        @endif
                    </div>
                </div>

                <h3 class="font-semibold text-sm mb-2 text-slate-700">Módulos habilitados</h3>
                <div class="flex flex-wrap gap-1.5 mb-5">
                    @foreach (\App\Models\Plano::MODULOS_DISPONIVEIS as $chave => $rotulo)
                        @if ($planoAtual->temModulo($chave))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs rounded-full border border-emerald-200">
                                <i class="ri-check-line"></i> {{ $rotulo }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-50 text-slate-400 text-xs rounded-full border border-slate-200">
                                <i class="ri-close-line"></i> {{ $rotulo }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <p class="font-semibold text-amber-800"><i class="ri-information-line"></i> Sem plano atribuído</p>
                    <p class="text-sm text-amber-700">Escolha um plano ao lado pra começar.</p>
                </div>
            @endif

            @if ($cobrancaPendente)
                <div @class([
                    'rounded-xl p-4 border-2 space-y-4',
                    'bg-amber-50 border-amber-300' => $statusInad === 'aviso',
                    'bg-orange-50 border-orange-300' => $statusInad === 'bloqueio_parcial',
                    'bg-rose-50 border-rose-300' => $statusInad === 'bloqueio_total',
                    'bg-blue-50 border-blue-200' => in_array($statusInad, ['em_dia', 'trial']),
                ])>
                    @php $meta = $cobrancaPendente->meta ?? []; @endphp
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div>
                            <p class="font-semibold text-slate-800">Cobrança pendente</p>
                            <p class="text-sm text-slate-600">
                                R$ {{ number_format($cobrancaPendente->valor, 2, ',', '.') }} ·
                                vence em {{ $cobrancaPendente->vencimento->format('d/m/Y') }}
                                @if ($cobrancaPendente->vencida())
                                    <span class="text-rose-600 font-semibold">(vencida)</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($cobrancaPendente->link_pagamento)
                                <a href="{{ $cobrancaPendente->link_pagamento }}" target="_blank"
                                   class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">
                                    <i class="ri-external-link-line"></i> Abrir no gateway
                                </a>
                            @endif
                            <form action="{{ route('admin.meu-plano.cobrancas.cancelar', $cobrancaPendente) }}" method="POST"
                                  onsubmit="return confirm('Cancelar essa cobrança pendente? Isso libera você pra trocar de plano.')">
                                @csrf
                                <button class="px-3 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg text-xs font-semibold">
                                    <i class="ri-close-circle-line"></i> Cancelar cobrança
                                </button>
                            </form>
                        </div>
                    </div>

                    @if (!empty($meta['pix_qr_code']) || !empty($meta['pix_copia_cola']))
                        <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start pt-3 border-t border-current/20"
                             x-data="{ copiado: false }">
                            @if (!empty($meta['pix_qr_code']) || !empty($meta['pix_qr_code_svg']))
                                <div class="bg-white p-2 rounded-lg border border-slate-200 inline-block w-48">
                                    @if (!empty($meta['pix_qr_code']))
                                        <img src="data:image/png;base64,{{ $meta['pix_qr_code'] }}" alt="QR PIX" class="w-full">
                                    @else
                                        <div class="[&_svg]:w-full [&_svg]:h-auto">{!! $meta['pix_qr_code_svg'] !!}</div>
                                    @endif
                                </div>
                            @endif
                            <div class="space-y-2">
                                <div>
                                    <p class="text-xs text-slate-600 font-semibold uppercase tracking-wider mb-1">PIX copia e cola</p>
                                    <textarea readonly rows="3"
                                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono bg-white"
                                              x-ref="codigo">{{ $meta['pix_copia_cola'] }}</textarea>
                                </div>
                                <button type="button" @click="$refs.codigo.select(); document.execCommand('copy'); copiado=true; setTimeout(()=>copiado=false,2000)"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">
                                    <i class="ri-file-copy-line"></i>
                                    <span x-text="copiado ? 'Copiado!' : 'Copiar código'"></span>
                                </button>
                                @if (!empty($meta['pix_expira_em']))
                                    <p class="text-[11px] text-slate-500">
                                        <i class="ri-time-line"></i> QR expira em {{ \Carbon\Carbon::parse($meta['pix_expira_em'])->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-500"><i class="ri-loader-line animate-spin"></i> Aguardando geração do código PIX...</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-lg mb-4">Consumo atual</h2>
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

        @if ($cobrancas->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-6" x-data="{ aberto: null }">
                <h2 class="font-semibold text-lg mb-4">Histórico de cobranças</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 border-b">
                        <tr class="text-left">
                            <th class="py-2">Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Pago em</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cobrancas as $c)
                            <tr class="border-b last:border-b-0">
                                <td class="py-2">{{ $c->vencimento->format('d/m/Y') }}</td>
                                <td class="font-semibold">R$ {{ number_format($c->valor, 2, ',', '.') }}</td>
                                <td>
                                    <span @class([
                                        'text-xs px-2 py-0.5 rounded-full',
                                        'bg-emerald-100 text-emerald-700' => $c->status === 'pago',
                                        'bg-amber-100 text-amber-700' => $c->status === 'pendente' && !$c->vencida(),
                                        'bg-rose-100 text-rose-700' => $c->status === 'pendente' && $c->vencida(),
                                        'bg-slate-200 text-slate-500' => in_array($c->status, ['cancelado', 'estornado']),
                                    ])>
                                        {{ $c->status === 'pendente' && $c->vencida() ? 'Vencida' : ucfirst($c->status) }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $c->pago_em?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <button type="button" @click="aberto = {{ $c->id }}"
                                            class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">
                                        <i class="ri-eye-line"></i> Ver
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Modais (um por cobrança) --}}
                @foreach ($cobrancas as $c)
                    @php $m = $c->meta ?? []; @endphp
                    <div x-show="aberto === {{ $c->id }}" x-cloak
                         x-transition.opacity
                         @keydown.escape.window="aberto = null"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
                        <div @click.away="aberto = null"
                             class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                            <div class="p-5 border-b border-slate-200 flex items-start justify-between">
                                <div>
                                    <p class="text-xs text-slate-500 uppercase tracking-wider">Cobrança #{{ $c->id }}</p>
                                    <p class="text-2xl font-bold mt-1">R$ {{ number_format($c->valor, 2, ',', '.') }}</p>
                                    <p class="text-sm text-slate-600 mt-1">
                                        Vence em {{ $c->vencimento->format('d/m/Y') }}
                                        @if ($c->vencida())
                                            <span class="text-rose-600 font-semibold">(vencida)</span>
                                        @endif
                                    </p>
                                </div>
                                <button type="button" @click="aberto = null" class="text-slate-400 hover:text-slate-600">
                                    <i class="ri-close-line text-2xl"></i>
                                </button>
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs text-slate-500">Status</p>
                                        <p class="font-medium">{{ $c->status === 'pendente' && $c->vencida() ? 'Vencida' : ucfirst($c->status) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Forma de pagamento</p>
                                        <p class="font-medium">{{ ucfirst($c->forma_pagamento ?? '—') }}</p>
                                    </div>
                                    @if ($c->pago_em)
                                        <div>
                                            <p class="text-xs text-slate-500">Pago em</p>
                                            <p class="font-medium text-emerald-700">{{ $c->pago_em->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @endif
                                </div>

                                @if ($c->status === 'pago')
                                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm">
                                        <i class="ri-checkbox-circle-fill"></i> Pagamento confirmado.
                                    </div>
                                @elseif ($c->status === 'cancelado')
                                    <div class="p-4 bg-slate-100 border border-slate-200 rounded-lg text-slate-600 text-sm">
                                        <i class="ri-close-circle-line"></i> Esta cobrança foi cancelada.
                                    </div>
                                @elseif (!empty($m['pix_qr_code']) || !empty($m['pix_qr_code_svg']) || !empty($m['pix_copia_cola']))
                                    <div x-data="{ copiado: false }" class="space-y-3">
                                        @if (!empty($m['pix_qr_code']))
                                            <div class="bg-white p-3 rounded-lg border border-slate-200 inline-block w-48 mx-auto">
                                                <img src="data:image/png;base64,{{ $m['pix_qr_code'] }}" alt="QR PIX" class="w-full">
                                            </div>
                                        @elseif (!empty($m['pix_qr_code_svg']))
                                            <div class="bg-white p-3 rounded-lg border border-slate-200 inline-block w-48 mx-auto [&_svg]:w-full">{!! $m['pix_qr_code_svg'] !!}</div>
                                        @endif
                                        @if (!empty($m['pix_copia_cola']))
                                            <div>
                                                <p class="text-xs text-slate-600 font-semibold uppercase tracking-wider mb-1">PIX copia e cola</p>
                                                <textarea readonly rows="3" x-ref="codigo{{ $c->id }}"
                                                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">{{ $m['pix_copia_cola'] }}</textarea>
                                                <button type="button"
                                                        @click="$refs['codigo{{ $c->id }}'].select(); document.execCommand('copy'); copiado=true; setTimeout(()=>copiado=false,2000)"
                                                        class="mt-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">
                                                    <i class="ri-file-copy-line"></i>
                                                    <span x-text="copiado ? 'Copiado!' : 'Copiar código'"></span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 text-sm">
                                        <i class="ri-loader-line"></i> Sem PIX gerado ainda pra essa cobrança.
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-2 pt-3 border-t border-slate-100">
                                    @if ($c->link_pagamento)
                                        <a href="{{ $c->link_pagamento }}" target="_blank"
                                           class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">
                                            <i class="ri-external-link-line"></i> Abrir no gateway
                                        </a>
                                    @endif
                                    @if ($c->status === 'pendente')
                                        <form action="{{ route('admin.meu-plano.cobrancas.cancelar', $c) }}" method="POST"
                                              onsubmit="return confirm('Cancelar essa cobrança pendente?')">
                                            @csrf
                                            <button class="px-3 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg text-xs font-semibold">
                                                <i class="ri-close-circle-line"></i> Cancelar cobrança
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <div class="bg-white rounded-xl shadow-sm p-5 sticky top-4">
            <h2 class="font-semibold text-lg mb-4">Planos disponíveis</h2>
            <div class="space-y-3">
                @forelse ($planosDisponiveis as $p)
                    @php
                        $atual = $planoAtual && $planoAtual->id === $p->id;
                        $superior = $planoAtual && $p->preco_mensal > $planoAtual->preco_mensal;
                    @endphp
                    <div @class([
                        'border-2 rounded-xl p-4 transition',
                        'border-indigo-500 bg-indigo-50' => $atual,
                        'border-slate-200 hover:border-slate-300' => !$atual,
                    ])>
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">{{ $p->nome }}</p>
                            @if ($atual)
                                <span class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded-full">Atual</span>
                            @elseif ($superior)
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Upgrade</span>
                            @endif
                        </div>
                        <p class="text-2xl font-bold mt-1">
                            R$ {{ number_format($p->preco_mensal, 2, ',', '.') }}<span class="text-xs text-slate-500 font-normal">/mês</span>
                        </p>
                        @if ($p->descricao)
                            <p class="text-xs text-slate-500 mt-1">{{ $p->descricao }}</p>
                        @endif

                        <ul class="text-xs text-slate-600 mt-3 space-y-0.5">
                            @if ($p->limite_clientes)<li>· Até {{ number_format($p->limite_clientes, 0, ',', '.') }} clientes</li>@else<li>· Clientes ilimitados</li>@endif
                            @php $mods = array_slice($p->modulos ?? [], 0, 5); @endphp
                            @foreach ($mods as $m)
                                <li>· {{ \App\Models\Plano::MODULOS_DISPONIVEIS[$m] ?? $m }}</li>
                            @endforeach
                            @if (count($p->modulos ?? []) > 5)
                                <li class="text-slate-400">+ {{ count($p->modulos) - 5 }} módulos</li>
                            @endif
                        </ul>

                        @if (!$atual)
                            <form action="{{ route('admin.meu-plano.upgrade', $p) }}" method="POST" class="mt-3"
                                  onsubmit="return confirm('Mudar pra plano {{ $p->nome }}? Uma cobrança será gerada.')">
                                @csrf
                                <button class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold">
                                    {{ $superior ? 'Fazer upgrade' : 'Mudar pra esse plano' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Sem planos cadastrados.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
