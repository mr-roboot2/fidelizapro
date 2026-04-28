@extends('layouts.admin')
@section('title', 'Relatórios')
@section('content')
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="text-xs text-slate-500">Período de</label>
        <input type="date" name="de" value="{{ $de }}" class="block px-3 py-2 border border-slate-300 rounded-lg text-sm">
    </div>
    <div>
        <label class="text-xs text-slate-500">até</label>
        <input type="date" name="ate" value="{{ $ate }}" class="block px-3 py-2 border border-slate-300 rounded-lg text-sm">
    </div>
    <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm">
        <i class="ri-search-line"></i> Gerar
    </button>
</form>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['Faturamento', 'R$ '.number_format($totalVendas, 2, ',', '.'), 'ri-money-dollar-circle-line', 'text-emerald-600'],
        ['Compras', $totalCompras, 'ri-shopping-cart-line', 'text-indigo-600'],
        ['Ticket médio', 'R$ '.number_format($ticketMedio, 2, ',', '.'), 'ri-line-chart-line', 'text-blue-600'],
        ['Novos clientes', $novosClientesPeriodo, 'ri-user-add-line', 'text-purple-600'],
        ['Pontos gerados', number_format($totalPontosGerados, 0, ',', '.'), 'ri-coin-line', 'text-amber-600'],
        ['Cashback gerado', 'R$ '.number_format($totalCashbackGerado, 2, ',', '.'), 'ri-wallet-line', 'text-emerald-600'],
        ['Resgates', $resgatesPeriodo, 'ri-gift-line', 'text-pink-600'],
        ['NPS', $nps['nps'].' ('.$nps['total'].' resp.)', 'ri-emotion-happy-line', 'text-fuchsia-600'],
    ] as [$titulo, $valor, $icone, $cor])
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="flex items-center gap-3">
                <i class="{{ $icone }} text-2xl {{ $cor }}"></i>
                <div>
                    <p class="text-xs text-slate-500">{{ $titulo }}</p>
                    <p class="text-lg font-bold">{{ $valor }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold mb-4">Vendas no período</h3>
    <canvas id="chartPeriodo" height="80"></canvas>
</div>

<div class="bg-white rounded-xl shadow-sm p-5">
    <h3 class="font-semibold mb-3">Top 10 clientes do período</h3>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left p-2">Cliente</th>
                <th class="text-right p-2">Compras</th>
                <th class="text-right p-2">Total gasto</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($topClientesPeriodo as $tc)
                <tr>
                    <td class="p-2">{{ $tc->cliente?->nome ?? '—' }}</td>
                    <td class="p-2 text-right">{{ $tc->qtd }}</td>
                    <td class="p-2 text-right font-semibold text-emerald-600">R$ {{ number_format($tc->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
new Chart(document.getElementById('chartPeriodo'), {
    type: 'bar',
    data: {
        labels: @json($vendasPorDia->pluck('dia')->map(fn($d)=>\Carbon\Carbon::parse($d)->format('d/m'))),
        datasets: [{
            label: 'Vendas (R$)',
            data: @json($vendasPorDia->pluck('total')),
            backgroundColor: '#6366f1',
            borderRadius: 6,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}}
});
</script>
@endsection
