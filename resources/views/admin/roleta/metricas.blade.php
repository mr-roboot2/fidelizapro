@extends('layouts.admin')
@section('title', 'Métricas da roleta')
@section('content')
@php
    $tiposResultado = [
        'pontos'      => ['label' => 'Pontos extras', 'cor' => '#3b82f6'],
        'recompensa'  => ['label' => 'Prêmio do catálogo', 'cor' => '#ef4444'],
        'nova_chance' => ['label' => 'Nova chance', 'cor' => '#10b981'],
        'consolacao'  => ['label' => 'Consolação', 'cor' => '#94a3b8'],
    ];
    $totalDist = $distribuicao->sum();
@endphp

<div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-2">
        <a href="{{ route('admin.roleta.index') }}" class="text-sm text-indigo-600 hover:underline">
            <i class="ri-arrow-left-line"></i> Voltar pra configuração
        </a>
        <p class="text-xs text-slate-500">Janela de análise: últimos 30 dias</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center mb-2">
                <i class="ri-bubble-chart-line text-xl text-indigo-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total de giros</p>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($kpi['total'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $kpi['hoje'] }} hoje · {{ $kpi['semana'] }} na semana</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-2">
                <i class="ri-coin-line text-xl text-amber-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Pontos distribuídos</p>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($kpi['pontos_total'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">+{{ $kpi['pontos_hoje'] }} hoje</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center mb-2">
                <i class="ri-gift-line text-xl text-rose-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Prêmios ganhos</p>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['recompensas'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $kpi['clientes_unicos'] }} cliente(s) único(s)</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-2">
                <i class="ri-time-line text-xl text-emerald-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Giros pendentes</p>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['saldo_pendente'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Saldo não usado pelos clientes</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
            <h2 class="text-base font-semibold mb-3">Giros nos últimos 30 dias</h2>
            <canvas id="chart-serie" height="100"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold mb-3">Distribuição por tipo</h2>
            @if ($totalDist === 0)
                <p class="text-sm text-slate-400 text-center py-10">Ainda sem giros registrados.</p>
            @else
                <canvas id="chart-dist" height="160"></canvas>
                <div class="mt-4 space-y-1.5 text-sm">
                    @foreach ($tiposResultado as $tipo => $info)
                        @php $qtd = (int) ($distribuicao[$tipo] ?? 0); @endphp
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full" style="background:{{ $info['cor'] }}"></span>
                            <span class="flex-1 text-slate-600">{{ $info['label'] }}</span>
                            <span class="font-semibold text-slate-700">{{ $qtd }}</span>
                            <span class="text-xs text-slate-400 w-12 text-right">{{ $totalDist > 0 ? round($qtd / $totalDist * 100, 1) : 0 }}%</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold mb-3">Top prêmios sorteados</h2>
            @if ($topPremios->isEmpty())
                <p class="text-sm text-slate-400 text-center py-6">Sem dados ainda.</p>
            @else
                <div class="space-y-2">
                    @php $maxPremio = $topPremios->max('total') ?: 1; @endphp
                    @foreach ($topPremios as $p)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-0.5">
                                <span class="flex items-center gap-2">
                                    <span class="inline-block w-3 h-3 rounded" style="background:{{ $p['cor'] }}"></span>
                                    <span class="text-slate-700">{{ $p['label'] }}</span>
                                </span>
                                <span class="font-semibold text-slate-700">{{ $p['total'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full" style="width:{{ round($p['total'] / $maxPremio * 100) }}%;background:{{ $p['cor'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold mb-3">Top ganhadores</h2>
            @if ($topGanhadores->isEmpty())
                <p class="text-sm text-slate-400 text-center py-6">Sem dados ainda.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 border-b">
                        <tr class="text-left"><th class="py-2">#</th><th>Cliente</th><th class="text-right">Giros</th><th class="text-right">Pontos</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($topGanhadores as $i => $g)
                            <tr class="border-b last:border-b-0">
                                <td class="py-2 text-slate-400">{{ $i + 1 }}</td>
                                <td>
                                    <p class="font-medium text-slate-700">{{ $g['nome'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $g['telefone'] }}</p>
                                </td>
                                <td class="text-right font-semibold">{{ $g['giros'] }}</td>
                                <td class="text-right text-amber-600 font-medium">{{ number_format($g['pontos'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="text-base font-semibold mb-3">Gatilhos disparados (últimos 30 dias)</h2>
        @if ($gatilhosDisparados->isEmpty())
            <p class="text-sm text-slate-400 text-center py-6">Nenhum gatilho disparado ainda. Cron roda diariamente às 06:00.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 border-b">
                    <tr class="text-left"><th class="py-2">Gatilho</th><th class="text-right">Disparos</th><th class="text-right">Giros creditados</th></tr>
                </thead>
                <tbody>
                    @foreach ($gatilhosDisparados as $g)
                        <tr class="border-b last:border-b-0">
                            <td class="py-2 font-medium text-slate-700">
                                {{ \App\Models\RoletaGatilho::TIPOS[$g->tipo]['rotulo'] ?? $g->tipo }}
                            </td>
                            <td class="text-right">{{ $g->total }}</td>
                            <td class="text-right text-emerald-600 font-semibold">+{{ $g->giros }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

<script>
const serieDados = @json($serie);
const distDados  = @json($distribuicao);
const tipoCores  = @json(array_map(fn($t) => $t['cor'], $tiposResultado));
const tipoLabels = @json(array_map(fn($t) => $t['label'], $tiposResultado));

new Chart(document.getElementById('chart-serie'), {
    type: 'line',
    data: {
        labels: serieDados.map(d => {
            const [y, m, day] = d.data.split('-');
            return `${day}/${m}`;
        }),
        datasets: [{
            label: 'Giros',
            data: serieDados.map(d => d.total),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderWidth: 2,
            tension: 0.35,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }},
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } }
        }
    }
});

const distEl = document.getElementById('chart-dist');
if (distEl) {
    const tipos = Object.keys(tipoLabels);
    new Chart(distEl, {
        type: 'doughnut',
        data: {
            labels: tipos.map(t => tipoLabels[t]),
            datasets: [{
                data: tipos.map(t => distDados[t] || 0),
                backgroundColor: tipos.map(t => tipoCores[t]),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: { legend: { display: false }}
        }
    });
}
</script>
@endsection
