@extends('layouts.admin')
@section('title', 'AI Growth')
@section('content')

<div class="flex items-center justify-between flex-wrap gap-3 mb-4">
    <form method="GET" class="bg-white rounded-xl shadow-sm p-3 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-xs text-slate-500 block">De</label>
            <input type="date" name="de" value="{{ $de->format('Y-m-d') }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="text-xs text-slate-500 block">Até</label>
            <input type="date" name="ate" value="{{ $ate->format('Y-m-d') }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        </div>
        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
            <i class="ri-search-line"></i> Filtrar
        </button>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('admin.ai-growth.export.pdf', ['de' => $de->format('Y-m-d'), 'ate' => $ate->format('Y-m-d')]) }}"
           class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium">
            <i class="ri-file-pdf-2-line"></i> Exportar PDF
        </a>
        <a href="{{ route('admin.ai-growth.export.csv', ['de' => $de->format('Y-m-d'), 'ate' => $ate->format('Y-m-d')]) }}"
           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium">
            <i class="ri-file-excel-2-line"></i> Exportar CSV
        </a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @php
        $deltaIcon = fn($v) => $v === null ? '' : ($v >= 0 ? '<i class="ri-arrow-up-line text-emerald-600"></i>' : '<i class="ri-arrow-down-line text-rose-600"></i>');
        $deltaCor  = fn($v) => $v === null ? 'text-slate-400' : ($v >= 0 ? 'text-emerald-600' : 'text-rose-600');
    @endphp
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Faturamento</p>
                <p class="text-2xl font-bold mt-1">R$ {{ number_format($kpi['faturamento'], 2, ',', '.') }}</p>
                @if ($delta['faturamento'] !== null)
                    <p class="text-xs {{ $deltaCor($delta['faturamento']) }} mt-1">
                        {!! $deltaIcon($delta['faturamento']) !!} {{ abs($delta['faturamento']) }}% vs período anterior
                    </p>
                @endif
            </div>
            <i class="ri-money-dollar-circle-line text-2xl text-emerald-600"></i>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Vendas</p>
                <p class="text-2xl font-bold mt-1">{{ number_format($kpi['vendas'], 0, ',', '.') }}</p>
                @if ($delta['vendas'] !== null)
                    <p class="text-xs {{ $deltaCor($delta['vendas']) }} mt-1">
                        {!! $deltaIcon($delta['vendas']) !!} {{ abs($delta['vendas']) }}%
                    </p>
                @endif
            </div>
            <i class="ri-shopping-cart-line text-2xl text-indigo-600"></i>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Ticket médio</p>
                <p class="text-2xl font-bold mt-1">R$ {{ number_format($kpi['ticket_medio'], 2, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $kpi['clientes_unicos'] }} clientes únicos</p>
            </div>
            <i class="ri-line-chart-line text-2xl text-blue-600"></i>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Novos clientes</p>
                <p class="text-2xl font-bold mt-1">{{ $kpi['novos_clientes'] }}</p>
                <p class="text-xs text-slate-400 mt-1">retenção {{ $retencao }}%</p>
            </div>
            <i class="ri-user-add-line text-2xl text-purple-600"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @php
        $kpisExtras = [
            ['Pontos gerados',  number_format($kpi['pontos_gerados'], 0, ',', '.'),    'ri-coin-line',                'text-amber-600'],
            ['Cashback gerado', 'R$ '.number_format($kpi['cashback_gerado'], 2, ',', '.'), 'ri-wallet-line',           'text-teal-600'],
            ['Resgates',        $kpi['resgates'],                                       'ri-gift-line',                'text-pink-600'],
            ['NPS',             $nps['nps'].' ('.$nps['total'].' resp.)',               'ri-emotion-happy-line',       'text-fuchsia-600'],
        ];
    @endphp
    @foreach ($kpisExtras as [$t, $v, $ic, $cor])
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="flex items-center gap-3">
                <i class="{{ $ic }} text-2xl {{ $cor }}"></i>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">{{ $t }}</p>
                    <p class="text-lg font-bold">{{ $v }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold mb-3 text-slate-700">Faturamento dia a dia</h3>
    <canvas id="chart-diario" height="80"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold mb-3 text-slate-700 flex items-center gap-2">
            <i class="ri-arrow-up-circle-line text-emerald-600"></i> Top 5 dias do período
        </h3>
        @if ($topDias->isEmpty())
            <p class="text-sm text-slate-400 text-center py-6">Sem vendas no período.</p>
        @else
            @php $maxTop = $topDias->max('total') ?: 1; @endphp
            <div class="space-y-2">
                @foreach ($topDias as $d)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>{{ $d['dia'] }} <span class="text-xs text-slate-400">({{ $d['vendas'] }} vendas)</span></span>
                            <span class="font-semibold text-emerald-600">R$ {{ number_format($d['total'], 2, ',', '.') }}</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500" style="width: {{ round($d['total'] / $maxTop * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold mb-3 text-slate-700 flex items-center gap-2">
            <i class="ri-arrow-down-circle-line text-rose-600"></i> 5 dias mais fracos
        </h3>
        @if ($bottomDias->isEmpty())
            <p class="text-sm text-slate-400 text-center py-6">Sem vendas no período.</p>
        @else
            @php $maxBot = $bottomDias->max('total') ?: 1; @endphp
            <div class="space-y-2">
                @foreach ($bottomDias as $d)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>{{ $d['dia'] }} <span class="text-xs text-slate-400">({{ $d['vendas'] }} vendas)</span></span>
                            <span class="font-semibold text-rose-600">R$ {{ number_format($d['total'], 2, ',', '.') }}</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-rose-400" style="width: {{ round($d['total'] / $maxBot * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold mb-3 text-slate-700">Faturamento médio por dia da semana <span class="text-xs text-slate-400 font-normal">(últimos 90 dias)</span></h3>
    <canvas id="chart-dow" height="80"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
        <h3 class="font-semibold mb-3 text-slate-700">Novos clientes (12 meses)</h3>
        <canvas id="chart-novos" height="80"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold mb-3 text-slate-700">Faixa etária dos clientes</h3>
        @if (collect($distIdade)->sum('total') === 0)
            <p class="text-sm text-slate-400 text-center py-6">Sem dados.</p>
        @else
            <canvas id="chart-idade" height="160"></canvas>
            <div class="mt-3 space-y-1 text-xs">
                @php $coresIdade = ['#10b981','#3b82f6','#a855f7','#f59e0b','#ef4444','#94a3b8']; @endphp
                @foreach ($distIdade as $i => $d)
                    @if ($d['total'] > 0)
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full" style="background:{{ $coresIdade[$i] }}"></span>
                            <span class="flex-1 text-slate-600">{{ $d['faixa'] }}</span>
                            <span class="font-semibold">{{ $d['total'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold mb-3 text-slate-700">Top 10 clientes do período</h3>
        @if ($topClientesPeriodo->isEmpty())
            <p class="text-sm text-slate-400 text-center py-6">Sem vendas no período.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 border-b">
                    <tr class="text-left"><th class="py-2">Cliente</th><th class="text-right">Compras</th><th class="text-right">Gasto</th></tr>
                </thead>
                <tbody>
                    @foreach ($topClientesPeriodo as $tc)
                        <tr class="border-b last:border-b-0">
                            <td class="py-2">
                                <p class="font-medium">{{ $tc->cliente->nome ?? '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $tc->cliente->telefone ?? '' }}</p>
                            </td>
                            <td class="text-right">{{ $tc->qtd }}</td>
                            <td class="text-right font-semibold text-emerald-600">R$ {{ number_format($tc->total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold mb-3 text-slate-700">Top 10 clientes (todos os tempos)</h3>
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 border-b">
                <tr class="text-left"><th class="py-2">Cliente</th><th class="text-right">Total compras</th><th class="text-right">Gasto</th></tr>
            </thead>
            <tbody>
                @foreach ($topClientesAll as $c)
                    <tr class="border-b last:border-b-0">
                        <td class="py-2">
                            <p class="font-medium">{{ $c->nome }}</p>
                            <p class="text-xs text-slate-400">{{ $c->telefone }}</p>
                        </td>
                        <td class="text-right">{{ $c->total_compras }}</td>
                        <td class="text-right font-semibold text-amber-600">R$ {{ number_format($c->total_gasto, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
const serie = @json($serie);
new Chart(document.getElementById('chart-diario'), {
    type: 'line',
    data: {
        labels: serie.map(s => s.dia),
        datasets: [{
            label: 'R$',
            data: serie.map(s => s.total),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderWidth: 2, tension: 0.35, fill: true, pointRadius: 2,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
});

const dow = @json($vendasPorDow);
new Chart(document.getElementById('chart-dow'), {
    type: 'bar',
    data: {
        labels: dow.map(d => d.nome.substring(0, 3)),
        datasets: [{
            label: 'R$ médio/dia',
            data: dow.map(d => d.media_dia),
            backgroundColor: '#a855f7',
            borderRadius: 6,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
});

const novos = @json($novosMensal);
new Chart(document.getElementById('chart-novos'), {
    type: 'bar',
    data: {
        labels: novos.map(m => {
            const [, mes] = m.mes.split('-');
            return ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][parseInt(mes, 10) - 1];
        }),
        datasets: [{
            data: novos.map(m => m.total),
            backgroundColor: '#10b981',
            borderRadius: 6,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true, ticks: { precision: 0 }}}}
});

const idadeEl = document.getElementById('chart-idade');
if (idadeEl) {
    const idade = @json($distIdade);
    const cores = ['#10b981','#3b82f6','#a855f7','#f59e0b','#ef4444','#94a3b8'];
    new Chart(idadeEl, {
        type: 'doughnut',
        data: {
            labels: idade.map(d => d.faixa),
            datasets: [{
                data: idade.map(d => d.total),
                backgroundColor: idade.map((_, i) => cores[i]),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: { responsive: true, cutout: '60%', plugins: { legend: { display: false }}}
    });
}
</script>
@endsection
