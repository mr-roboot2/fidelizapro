@extends('layouts.admin')
@section('title', 'Atividade suspeita')
@section('content')
<div class="bg-gradient-to-r from-rose-500 to-orange-500 text-white rounded-xl p-5 mb-6">
    <h2 class="font-bold text-lg flex items-center gap-2">
        <i class="ri-shield-keyhole-line"></i> Monitor antifraude
    </h2>
    <p class="text-white/80 text-sm mt-1">
        Padrões incomuns detectados nos últimos dias. Avalie cada caso — alguns podem ser legítimos.
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Resgates em rajada -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-700">
                <i class="ri-coupon-line text-amber-600"></i> Resgates em rajada (3+ em 24h)
            </h3>
        </div>
        @if ($resgatesEmRajada->isEmpty())
            <p class="p-4 text-sm text-slate-400">Nenhum cliente com mais de 2 resgates em 24h.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($resgatesEmRajada as $r)
                    <li class="p-3 flex justify-between items-center">
                        <div>
                            <a href="{{ route('admin.clientes.show', $r->cliente_id) }}" class="font-medium hover:underline">
                                {{ $r->cliente->nome }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $r->cliente->telefone }}</p>
                        </div>
                        <span class="text-sm bg-rose-100 text-rose-700 px-3 py-1 rounded-full font-bold">
                            {{ $r->total }} resgates
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- Compras grandes -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-700">
                <i class="ri-money-dollar-circle-line text-emerald-600"></i> Compras altas (3x ticket médio)
            </h3>
            <p class="text-xs text-slate-500">Ticket médio: R$ {{ number_format($ticketMedio, 2, ',', '.') }}</p>
        </div>
        @if ($comprasGrandes->isEmpty())
            <p class="p-4 text-sm text-slate-400">Nenhuma compra fora do padrão nos últimos 7 dias.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($comprasGrandes as $c)
                    <li class="p-3 flex justify-between items-center">
                        <div>
                            <a href="{{ route('admin.clientes.show', $c->cliente_id) }}" class="font-medium hover:underline">
                                {{ $c->cliente->nome }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $c->created_at->format('d/m H:i') }} • origem: {{ $c->origem }}</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-600">
                            R$ {{ number_format($c->valor, 2, ',', '.') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- IPs compartilhados -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-700">
                <i class="ri-global-line text-blue-600"></i> IPs com múltiplos clientes
            </h3>
            <p class="text-xs text-slate-500">3+ contas usando o mesmo IP recentemente</p>
        </div>
        @if ($ipsCompartilhados->isEmpty())
            <p class="p-4 text-sm text-slate-400">Nenhum IP suspeito.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($ipsCompartilhados as $ip)
                    <li class="p-3 flex justify-between items-center">
                        <code class="text-sm font-mono">{{ $ip->ultimo_ip }}</code>
                        <span class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold">
                            {{ $ip->total_clientes }} clientes
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- Cadastros em rajada -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-700">
                <i class="ri-user-add-line text-purple-600"></i> Cadastros em rajada (24h)
            </h3>
            <p class="text-xs text-slate-500">3+ contas criadas do mesmo IP</p>
        </div>
        @if ($cadastrosRajada->isEmpty())
            <p class="p-4 text-sm text-slate-400">Nenhum cadastro em rajada.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($cadastrosRajada as $c)
                    <li class="p-3 flex justify-between items-center">
                        <code class="text-sm font-mono">{{ $c->ultimo_ip }}</code>
                        <span class="text-sm bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-bold">
                            {{ $c->total }} cadastros
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="mt-6 p-4 bg-slate-50 rounded-xl text-sm text-slate-600">
    <p class="font-semibold mb-2">🛡️ Limites antifraude ativos:</p>
    <ul class="list-disc list-inside space-y-1 text-xs">
        <li>Login/registro/OTP: 10 tentativas/minuto por IP</li>
        <li>Webhook PDV: 60 chamadas/minuto por IP</li>
        <li>OTP: 3 códigos por telefone em 15 minutos, 5 tentativas por código</li>
        <li>Resgates: máximo 3 por cliente em 24h</li>
        <li>Cupom de parceiro: respeita limite_por_cliente do benefício</li>
    </ul>
</div>
@endsection
