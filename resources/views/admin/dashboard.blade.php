@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['Clientes', $totalClientes, 'ri-user-line', 'from-indigo-500 to-purple-500', $clientesAtivos.' ativos'],
            ['Faturamento', 'R$ '.number_format($faturamento, 2, ',', '.'), 'ri-money-dollar-circle-line', 'from-emerald-500 to-teal-500', $totalCompras.' compras'],
            ['Ticket médio', 'R$ '.number_format($ticketMedio, 2, ',', '.'), 'ri-line-chart-line', 'from-blue-500 to-cyan-500', 'por compra'],
            ['Pontos circulando', number_format($pontosEmCirculacao, 0, ',', '.'), 'ri-coin-line', 'from-amber-500 to-orange-500', 'em saldo'],
        ];
    @endphp
    @foreach ($cards as [$titulo, $valor, $icone, $gradiente, $sub])
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-500">{{ $titulo }}</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $valor }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $sub }}</p>
                </div>
                <div class="w-11 h-11 rounded-lg bg-gradient-to-br {{ $gradiente }} flex items-center justify-center text-white">
                    <i class="{{ $icone }} text-xl"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cards2 = [
            ['Cashback circulando', 'R$ '.number_format($cashbackEmCirculacao, 2, ',', '.'), 'ri-wallet-line', 'text-emerald-600'],
            ['Resgates pendentes', $resgatesPendentes, 'ri-time-line', 'text-amber-600'],
            ['Total resgates', $totalResgates, 'ri-gift-line', 'text-pink-600'],
            ['Campanhas ativas', $campanhasAtivas, 'ri-megaphone-line', 'text-purple-600'],
        ];
    @endphp
    @foreach ($cards2 as [$titulo, $valor, $icone, $cor])
        <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
            <i class="{{ $icone }} text-3xl {{ $cor }}"></i>
            <div>
                <p class="text-sm text-slate-500">{{ $titulo }}</p>
                <p class="text-xl font-bold">{{ $valor }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-700 mb-4">Vendas dos últimos 7 dias</h3>
        <canvas id="chartVendas" height="100"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-700 mb-4">Top clientes</h3>
        <ul class="space-y-3">
            @forelse ($topClientes as $cli)
                <li class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($cli->nome, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $cli->nome }}</p>
                        <p class="text-xs text-slate-500">R$ {{ number_format($cli->total_gasto, 2, ',', '.') }}</p>
                    </div>
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                        {{ number_format($cli->pontos_atual, 0, ',', '.') }} pts
                    </span>
                </li>
            @empty
                <p class="text-sm text-slate-400">Sem dados ainda.</p>
            @endforelse
        </ul>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-700">Compras recentes</h3>
            <a href="{{ route('admin.compras.index') }}" class="text-xs text-indigo-600">Ver todas</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($comprasRecentes as $c)
                <li class="py-2 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium">{{ $c->cliente->nome }}</p>
                        <p class="text-xs text-slate-500">{{ $c->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-emerald-600">R$ {{ number_format($c->valor, 2, ',', '.') }}</p>
                        <p class="text-xs text-amber-600">+{{ number_format($c->pontos_gerados, 0, ',', '.') }} pts</p>
                    </div>
                </li>
            @empty
                <p class="text-sm text-slate-400">Sem compras.</p>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-700">Resgates recentes</h3>
            <a href="{{ route('admin.resgates.index') }}" class="text-xs text-indigo-600">Ver todos</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($resgatesRecentes as $r)
                <li class="py-2 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-medium truncate">{{ $r->cliente->nome }}</p>
                        <p class="text-xs text-slate-500">{{ $r->recompensa->nome }}</p>
                    </div>
                    <span @class([
                        'text-xs px-2 py-0.5 rounded-full',
                        'bg-amber-100 text-amber-700' => $r->status === 'pendente',
                        'bg-blue-100 text-blue-700' => $r->status === 'aprovado',
                        'bg-emerald-100 text-emerald-700' => $r->status === 'entregue',
                        'bg-slate-200 text-slate-600' => $r->status === 'cancelado',
                    ])>{{ ucfirst($r->status) }}</span>
                </li>
            @empty
                <p class="text-sm text-slate-400">Sem resgates.</p>
            @endforelse
        </ul>
    </div>
</div>

<script>
const ctx = document.getElementById('chartVendas');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($vendasUltimos7Dias->pluck('dia')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
        datasets: [{
            label: 'Vendas (R$)',
            data: @json($vendasUltimos7Dias->pluck('total')),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderWidth: 2,
            tension: 0.35,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }},
        scales: { y: { beginAtZero: true }}
    }
});
</script>
@endsection
