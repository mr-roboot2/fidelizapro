@extends('layouts.super')
@section('title', $empresa->nome)
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl font-bold" style="background:{{ $empresa->cor_primaria }}">
                {{ strtoupper(substr($empresa->nome, 0, 1)) }}
            </div>
            <div>
                <h2 class="font-bold">{{ $empresa->nome }}</h2>
                <p class="text-sm text-slate-500">/{{ $empresa->slug }}</p>
            </div>
        </div>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">CNPJ</dt><dd>{{ $empresa->cnpj ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Telefone</dt><dd>{{ $empresa->telefone ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">E-mail</dt><dd>{{ $empresa->email ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Endereço</dt><dd class="text-right">{{ $empresa->endereco ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Pontos por R$</dt><dd>{{ $empresa->pontos_por_real }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Cashback</dt><dd>{{ $empresa->cashback_percentual }}%</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Validade pontos</dt><dd>{{ $empresa->validade_pontos_dias }} dias</dd></div>
        </dl>

        <div class="mt-5 flex gap-2">
            <form action="{{ route('super.impersonate.entrar', $empresa) }}" method="POST" class="flex-1">
                @csrf
                <button class="w-full text-sm bg-rose-600 text-white py-2 rounded-lg hover:bg-rose-700">
                    <i class="ri-spy-line"></i> Acessar painel
                </button>
            </form>
            <a href="{{ route('super.empresas.edit', $empresa) }}" class="px-3 text-sm bg-slate-200 py-2 rounded-lg">
                <i class="ri-edit-line"></i>
            </a>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            @php
                $stats = [
                    ['Clientes', $empresa->clientes_count, 'ri-user-line', 'text-indigo-600'],
                    ['Compras', $empresa->compras_count, 'ri-shopping-cart-line', 'text-emerald-600'],
                    ['Faturamento', 'R$ '.number_format($empresa->faturamento ?? 0, 2, ',', '.'), 'ri-money-dollar-circle-line', 'text-emerald-600'],
                    ['Recompensas', $empresa->recompensas_count, 'ri-gift-line', 'text-pink-600'],
                    ['Resgates', $empresa->resgates_count, 'ri-coupon-line', 'text-amber-600'],
                    ['Campanhas', $empresa->campanhas_count, 'ri-megaphone-line', 'text-purple-600'],
                ];
            @endphp
            @foreach ($stats as [$titulo, $valor, $icone, $cor])
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <i class="{{ $icone }} text-2xl {{ $cor }}"></i>
                    <p class="text-xs text-slate-500 mt-2">{{ $titulo }}</p>
                    <p class="text-lg font-bold">{{ $valor }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-4 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold">Usuários desta empresa</h3>
                <a href="{{ route('super.users.create') }}?empresa_id={{ $empresa->id }}" class="text-xs text-rose-600">+ Novo</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left p-3">Nome</th>
                        <th class="text-left p-3">E-mail</th>
                        <th class="text-left p-3">Papel</th>
                        <th class="text-center p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($admins as $u)
                        <tr>
                            <td class="p-3 font-medium">{{ $u->name }}</td>
                            <td class="p-3 text-slate-600">{{ $u->email }}</td>
                            <td class="p-3"><span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $u->role }}</span></td>
                            <td class="p-3 text-center">
                                @if ($u->ativo)<span class="text-emerald-600">●</span>@else<span class="text-slate-400">●</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
