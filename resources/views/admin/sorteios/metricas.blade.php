@extends('layouts.admin')
@section('title', 'Métricas dos sorteios')
@section('content')
@php
    $origens = [
        'roleta'     => ['label' => 'Roleta da Sorte',     'cor' => '#6366f1'],
        'compra'     => ['label' => 'Compra',              'cor' => '#3b82f6'],
        'manual'     => ['label' => 'Crédito manual',      'cor' => '#a855f7'],
        'consolacao' => ['label' => 'Consolação',          'cor' => '#94a3b8'],
    ];
    $totalOrigem = $porOrigem->sum();
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <a href="{{ route('admin.sorteios.index') }}" class="text-sm text-indigo-600 hover:underline">
            <i class="ri-arrow-left-line"></i> Voltar pra lista
        </a>
        <p class="text-xs text-slate-500">Janela de análise: últimos 30 dias</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center mb-2">
                <i class="ri-ticket-2-line text-xl text-indigo-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Sorteios totais</p>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['total_sorteios'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $kpi['ativos'] }} ativos · {{ $kpi['sorteados_30d'] }} sorteados (30d)</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-2">
                <i class="ri-coupon-2-line text-xl text-amber-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Bilhetes distribuídos</p>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($kpi['bilhetes_total'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $kpi['bilhetes_mes'] }} no mês corrente</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-2">
                <i class="ri-user-line text-xl text-emerald-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Participantes únicos</p>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['clientes_unicos'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Clientes distintos com bilhete</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center mb-2">
                <i class="ri-trophy-line text-xl text-rose-600"></i>
            </div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Vencedores 30d</p>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['sorteados_30d'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Sorteios concluídos</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
            <h2 class="text-base font-semibold mb-3">Bilhetes distribuídos nos últimos 30 dias</h2>
            <canvas id="chart-serie" height="100"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold mb-3">Origem dos bilhetes</h2>
            @if ($totalOrigem === 0)
                <p class="text-sm text-slate-400 text-center py-10">Sem bilhetes ainda.</p>
            @else
                <canvas id="chart-origem" height="160"></canvas>
                <div class="mt-4 space-y-1.5 text-sm">
                    @foreach ($origens as $k => $info)
                        @php $qtd = (int) ($porOrigem[$k] ?? 0); @endphp
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full" style="background:{{ $info['cor'] }}"></span>
                            <span class="flex-1 text-slate-600">{{ $info['label'] }}</span>
                            <span class="font-semibold text-slate-700">{{ $qtd }}</span>
                            <span class="text-xs text-slate-400 w-12 text-right">{{ $totalOrigem > 0 ? round($qtd / $totalOrigem * 100, 1) : 0 }}%</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold mb-3">Top 5 sorteios por engajamento</h2>
            @if ($topSorteios->isEmpty())
                <p class="text-sm text-slate-400 text-center py-6">Nenhum sorteio criado.</p>
            @else
                @php $maxTop = $topSorteios->max('bilhetes_count') ?: 1; @endphp
                <div class="space-y-2">
                    @foreach ($topSorteios as $s)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-0.5">
                                <a href="{{ route('admin.sorteios.show', $s) }}" class="text-slate-700 hover:underline truncate">{{ $s->nome }}</a>
                                <span class="font-semibold text-slate-700">{{ $s->bilhetes_count }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-indigo-500" style="width:{{ round($s->bilhetes_count / $maxTop * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold mb-3">Próximos sorteios</h2>
            @if ($proximos->isEmpty())
                <p class="text-sm text-slate-400 text-center py-6">Nenhum sorteio agendado.</p>
            @else
                <div class="space-y-2">
                    @foreach ($proximos as $s)
                        <a href="{{ route('admin.sorteios.show', $s) }}" class="flex items-center justify-between p-2 rounded hover:bg-slate-50 -mx-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-700 truncate">{{ $s->nome }}</p>
                                <p class="text-xs text-slate-400">{{ $s->data_sorteio->format('d/m/Y') }} · {{ $s->bilhetes_count }} bilhetes</p>
                            </div>
                            <span @class([
                                'text-[10px] px-2 py-0.5 rounded-full',
                                'bg-emerald-100 text-emerald-700' => $s->status === 'ativo',
                                'bg-amber-100 text-amber-700' => $s->status === 'planejado',
                            ])>{{ ucfirst($s->status) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="text-base font-semibold mb-3">Vencedores recentes</h2>
        @if ($vencedoresRecentes->isEmpty())
            <p class="text-sm text-slate-400 text-center py-6">Nenhum sorteio concluído ainda.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-500 border-b">
                    <tr class="text-left">
                        <th class="py-2">Sorteio</th>
                        <th>Vencedor</th>
                        <th>Bilhete</th>
                        <th class="text-right">Sorteado em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vencedoresRecentes as $s)
                        <tr class="border-b last:border-b-0">
                            <td class="py-2">
                                <a href="{{ route('admin.sorteios.show', $s) }}" class="font-medium text-slate-700 hover:underline">{{ $s->nome }}</a>
                            </td>
                            <td>
                                <p class="text-slate-700">{{ $s->vencedor->nome ?? '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $s->vencedor->telefone ?? '' }}</p>
                            </td>
                            <td class="font-mono text-amber-700">{{ $s->vencedorBilhete?->numeroFormatado() ?? '—' }}</td>
                            <td class="text-right text-xs text-slate-500">{{ $s->sorteado_em?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
const serieDados   = @json($serie);
const origemDados  = @json($porOrigem);
const origemLabels = @json(array_map(fn($o) => $o['label'], $origens));
const origemCores  = @json(array_map(fn($o) => $o['cor'], $origens));

new Chart(document.getElementById('chart-serie'), {
    type: 'line',
    data: {
        labels: serieDados.map(d => {
            const [, m, day] = d.data.split('-');
            return `${day}/${m}`;
        }),
        datasets: [{
            label: 'Bilhetes',
            data: serieDados.map(d => d.total),
            borderColor: '#a855f7',
            backgroundColor: 'rgba(168,85,247,0.15)',
            borderWidth: 2, tension: 0.35, fill: true, pointRadius: 0, pointHoverRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }},
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } } }
    }
});

const origemEl = document.getElementById('chart-origem');
if (origemEl) {
    const tipos = Object.keys(origemLabels);
    new Chart(origemEl, {
        type: 'doughnut',
        data: {
            labels: tipos.map(t => origemLabels[t]),
            datasets: [{
                data: tipos.map(t => origemDados[t] || 0),
                backgroundColor: tipos.map(t => origemCores[t]),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: { responsive: true, cutout: '60%', plugins: { legend: { display: false }} }
    });
}
</script>
@endsection
