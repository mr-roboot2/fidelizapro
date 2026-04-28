@extends('layouts.admin')
@section('title', $cliente->nome)
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold">
                {{ strtoupper(substr($cliente->nome, 0, 1)) }}
            </div>
            <div>
                <h2 class="font-bold">{{ $cliente->nome }}</h2>
                <p class="text-sm text-slate-500">{{ $cliente->telefone }}</p>
            </div>
        </div>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">E-mail</dt><dd>{{ $cliente->email ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">CPF</dt><dd>{{ $cliente->cpf ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Aniversário</dt><dd>{{ $cliente->data_nascimento?->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Cadastro</dt><dd>{{ $cliente->created_at->format('d/m/Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Última compra</dt><dd>{{ $cliente->ultima_compra?->format('d/m/Y') ?? 'nunca' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Código indicação</dt><dd class="font-mono">{{ $cliente->codigo_indicacao }}</dd></div>
        </dl>

        <div class="mt-5 grid grid-cols-2 gap-2 text-center">
            <div class="bg-amber-50 rounded-lg p-3">
                <p class="text-xs text-amber-700">Pontos</p>
                <p class="text-xl font-bold text-amber-700">{{ number_format($cliente->pontos_atual, 0, ',', '.') }}</p>
            </div>
            <div class="bg-emerald-50 rounded-lg p-3">
                <p class="text-xs text-emerald-700">Cashback</p>
                <p class="text-xl font-bold text-emerald-700">R$ {{ number_format($cliente->cashback_atual, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-5 flex gap-2">
            <a href="{{ route('admin.compras.create', ['cliente_id' => $cliente->id]) }}"
               class="flex-1 text-center text-sm bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
                <i class="ri-add-line"></i> Lançar compra
            </a>
            <a href="{{ route('admin.clientes.edit', $cliente) }}"
               class="px-3 text-center text-sm bg-slate-200 py-2 rounded-lg">
                <i class="ri-edit-line"></i>
            </a>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Últimas compras</h3>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->compras as $c)
                    <li class="py-2 flex justify-between">
                        <div>
                            <p class="font-medium">{{ $c->descricao ?? 'Compra' }}</p>
                            <p class="text-xs text-slate-500">{{ $c->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-emerald-600">R$ {{ number_format($c->valor, 2, ',', '.') }}</p>
                            <p class="text-xs text-amber-600">+{{ number_format($c->pontos_gerados, 0, ',', '.') }} pts</p>
                        </div>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm">Nenhuma compra ainda.</p>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Resgates</h3>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->resgates as $r)
                    <li class="py-2 flex justify-between">
                        <div>
                            <p class="font-medium">{{ $r->recompensa->nome }}</p>
                            <p class="text-xs text-slate-500">{{ $r->codigo }} • {{ $r->created_at->format('d/m/Y') }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ ucfirst($r->status) }}</span>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm">Nenhum resgate ainda.</p>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Movimentação de pontos</h3>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->transacoesPontos as $t)
                    <li class="py-2 flex justify-between">
                        <div>
                            <p class="font-medium">{{ $t->descricao }}</p>
                            <p class="text-xs text-slate-500">{{ $t->created_at->format('d/m/Y H:i') }} • {{ $t->origem }}</p>
                        </div>
                        <p class="font-semibold {{ $t->tipo === 'credito' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $t->tipo === 'credito' ? '+' : '−' }}{{ number_format($t->pontos, 0, ',', '.') }}
                        </p>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm">Sem movimentações.</p>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
