@extends('layouts.super')
@section('title', 'Cobrança #'.$cobranca->id)
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
    $meta = $cobranca->meta ?? [];
    $pixExpirado = !empty($meta['pix_expira_em']) && \Carbon\Carbon::parse($meta['pix_expira_em'])->isPast();
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">

    <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Cobrança #{{ $cobranca->id }}</p>
                <p class="text-3xl font-bold mt-1">R$ {{ number_format($cobranca->valor, 2, ',', '.') }}</p>
                <p class="text-sm text-slate-600 mt-1">
                    Vence em {{ $cobranca->vencimento->format('d/m/Y') }}
                    @if ($cobranca->vencida())
                        <span class="text-rose-600 font-semibold">(vencida há {{ (int) $cobranca->vencimento->diffInDays(now()) }} dia(s))</span>
                    @endif
                </p>
            </div>
            <span @class([
                'text-xs px-2 py-1 rounded-full font-semibold',
                'bg-emerald-100 text-emerald-700' => $cobranca->status === 'pago',
                'bg-amber-100 text-amber-700' => $cobranca->status === 'pendente' && !$cobranca->vencida(),
                'bg-rose-100 text-rose-700' => $cobranca->status === 'pendente' && $cobranca->vencida(),
                'bg-slate-200 text-slate-500' => in_array($cobranca->status, ['cancelado', 'estornado']),
            ])>{{ $cobranca->status === 'pendente' && $cobranca->vencida() ? 'Vencida' : ucfirst($cobranca->status) }}</span>
        </div>

        <div class="border-t pt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-xs text-slate-500">Empresa</p>
                <p class="font-medium">{{ $cobranca->empresa->nome ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Plano</p>
                <p class="font-medium">{{ $cobranca->assinatura->plano->nome ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Telefone</p>
                <p class="font-medium">{{ $cobranca->empresa->telefone ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Forma de pagamento</p>
                <p class="font-medium">{{ ucfirst($cobranca->forma_pagamento ?? 'não definida') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Gateway ID</p>
                <p class="font-mono text-xs break-all">{{ $cobranca->gateway_charge_id ?? '—' }}</p>
            </div>
            @if ($cobranca->pago_em)
                <div>
                    <p class="text-xs text-slate-500">Pago em</p>
                    <p class="font-medium text-emerald-700">{{ $cobranca->pago_em->format('d/m/Y H:i') }}</p>
                </div>
            @endif
        </div>

        <div class="border-t pt-4 flex flex-wrap gap-2">
            @if ($cobranca->status === 'pendente')
                <form action="{{ route('super.cobrancas.marcar-paga', $cobranca) }}" method="POST"
                      onsubmit="return confirm('Marcar como paga manualmente?')">@csrf
                    <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold">
                        <i class="ri-check-line"></i> Marcar como paga
                    </button>
                </form>
                <form action="{{ route('super.cobrancas.regerar-pix', $cobranca) }}" method="POST"
                      onsubmit="return confirm('Regerar PIX?')">@csrf
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">
                        <i class="ri-refresh-line"></i> Regerar PIX
                    </button>
                </form>
            @endif
            @if ($cobranca->status !== 'pago')
                @if ($cobranca->status !== 'cancelado')
                    <form action="{{ route('super.cobrancas.cancelar', $cobranca) }}" method="POST"
                          onsubmit="return confirm('Cancelar essa cobrança? Tenta cancelar também no Asaas.')">@csrf
                        <button class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold">
                            <i class="ri-close-circle-line"></i> Cancelar cobrança
                        </button>
                    </form>
                @endif
                <form action="{{ route('super.cobrancas.excluir', $cobranca) }}" method="POST"
                      onsubmit="return confirm('EXCLUIR essa cobrança permanentemente? Essa ação não pode ser desfeita.')">@csrf @method('DELETE')
                    <button class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-semibold">
                        <i class="ri-delete-bin-line"></i> Excluir
                    </button>
                </form>
            @endif
            <a href="{{ route('super.assinaturas.show', $cobranca->assinatura_id) }}"
               class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm">Ver assinatura</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold mb-3">PIX</h2>
        @if ($cobranca->status === 'pago')
            <p class="text-emerald-600"><i class="ri-checkbox-circle-fill text-2xl"></i> Pago</p>
        @elseif (empty($meta['pix_qr_code']) && empty($meta['pix_qr_code_svg']))
            <p class="text-sm text-slate-500"><i class="ri-loader-line"></i> Sem PIX gerado. Clique em "Regerar PIX" no painel ao lado.</p>
        @else
            <div x-data="{ copiado: false }">
                @if ($pixExpirado)
                    <div class="mb-3 p-2 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded">
                        <i class="ri-time-line"></i> Esse QR expirou em {{ \Carbon\Carbon::parse($meta['pix_expira_em'])->format('d/m/Y H:i') }} — regere antes de pagar.
                    </div>
                @endif

                <div class="bg-white p-3 rounded-lg border border-slate-200 inline-block w-56">
                    @if (!empty($meta['pix_qr_code']))
                        <img src="data:image/png;base64,{{ $meta['pix_qr_code'] }}" alt="QR PIX" class="w-full">
                    @else
                        <div class="[&_svg]:w-full [&_svg]:h-auto">{!! $meta['pix_qr_code_svg'] !!}</div>
                    @endif
                </div>

                <div class="mt-3">
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Copia e cola</p>
                    <textarea readonly rows="3" x-ref="codigo"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">{{ $meta['pix_copia_cola'] ?? '' }}</textarea>
                    <button type="button"
                            @click="$refs.codigo.select(); document.execCommand('copy'); copiado=true; setTimeout(()=>copiado=false,2000)"
                            class="mt-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">
                        <i class="ri-file-copy-line"></i>
                        <span x-text="copiado ? 'Copiado!' : 'Copiar código'"></span>
                    </button>
                </div>

                @if (!empty($meta['pix_expira_em']))
                    <p class="text-[11px] text-slate-500 mt-3">
                        <i class="ri-time-line"></i> Expira em {{ \Carbon\Carbon::parse($meta['pix_expira_em'])->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>

@endsection
