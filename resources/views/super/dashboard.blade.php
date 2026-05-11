@extends('layouts.super')
@section('title', 'Dashboard global')
@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['Empresas', $totalEmpresas, 'ri-building-line', 'from-rose-500 to-orange-500', $empresasAtivas.' ativas'],
            ['Faturamento total', 'R$ '.number_format($faturamentoTotal, 2, ',', '.'), 'ri-money-dollar-circle-line', 'from-emerald-500 to-teal-500', $totalCompras.' compras'],
            ['Clientes', number_format($totalClientes, 0, ',', '.'), 'ri-user-line', 'from-indigo-500 to-purple-500', 'em todas empresas'],
            ['Usuários admin', $totalUsers, 'ri-user-settings-line', 'from-blue-500 to-cyan-500', 'logins ativos'],
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

<h2 class="text-lg font-semibold text-slate-700 mb-3 flex items-center gap-2">
    <i class="ri-line-chart-line"></i> Métricas SaaS
</h2>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cardsSaaS = [
            ['MRR (Mensal)',  'R$ '.number_format($mrr, 2, ',', '.'),  'ri-pulse-line',          'from-emerald-500 to-teal-500', 'receita recorrente'],
            ['ARR (Anual)',   'R$ '.number_format($arr, 2, ',', '.'),  'ri-bar-chart-grouped-line','from-indigo-500 to-purple-500', 'projeção anual'],
            ['Inadimplência', $taxaInadimplencia.'%',                   'ri-error-warning-line',  'from-rose-500 to-pink-500',    $inadimplentes.' empresas'],
            ['Assinaturas',   $totalAssinaturas ?? 0,                   'ri-vip-crown-line',      'from-amber-500 to-orange-500', 'ativas + trial + inad.'],
        ];
    @endphp
    @foreach ($cardsSaaS as [$titulo, $valor, $icone, $gradiente, $sub])
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
        <h2 class="font-semibold text-slate-700 mb-3">Receita mensal (12 meses)</h2>
        <canvas id="chart-mrr" height="100"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-semibold text-slate-700 mb-3">Distribuição por plano</h2>
        @if ($distribuicaoPlanos->isEmpty() || $distribuicaoPlanos->sum('total') == 0)
            <p class="text-sm text-slate-400 text-center py-10">Sem assinaturas ativas.</p>
        @else
            <canvas id="chart-planos" height="160"></canvas>
            <div class="mt-3 space-y-1 text-xs">
                @php $coresPlano = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#a855f7', '#3b82f6']; @endphp
                @foreach ($distribuicaoPlanos as $i => $p)
                    @if ($p->total > 0)
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full" style="background:{{ $coresPlano[$i % count($coresPlano)] }}"></span>
                            <span class="flex-1 text-slate-600">{{ $p->nome }}</span>
                            <span class="font-semibold text-slate-700">{{ $p->total }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h2 class="font-semibold text-slate-700 mb-3">Novas assinaturas por mês</h2>
    <canvas id="chart-novas" height="80"></canvas>
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h3 class="font-semibold mb-4">Vendas globais — últimos 30 dias</h3>
    <canvas id="chartGlobal" height="80"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-semibold">Ranking de empresas</h3>
            <a href="{{ route('super.empresas.index') }}" class="text-xs text-rose-600">Ver todas</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Empresa</th>
                    <th class="text-right p-3">Clientes</th>
                    <th class="text-right p-3">Compras</th>
                    <th class="text-right p-3">Faturamento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($rankingEmpresas as $emp)
                    <tr>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded shrink-0" style="background:{{ $emp->cor_primaria }}"></div>
                                <a href="{{ route('super.empresas.show', $emp) }}" class="font-medium hover:underline">{{ $emp->nome }}</a>
                            </div>
                        </td>
                        <td class="p-3 text-right">{{ $emp->clientes_count }}</td>
                        <td class="p-3 text-right">{{ $emp->compras_count }}</td>
                        <td class="p-3 text-right font-semibold text-emerald-600">R$ {{ number_format($emp->faturamento ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-slate-500">Total de resgates</p>
                <p class="text-2xl font-bold text-pink-600">{{ $totalResgates }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Total de campanhas</p>
                <p class="text-2xl font-bold text-purple-600">{{ $totalCampanhas }}</p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-rose-500 to-orange-500 text-white rounded-xl p-5">
            <h3 class="font-semibold mb-2"><i class="ri-flashlight-line"></i> Ações rápidas</h3>
            <div class="space-y-2 mt-3">
                <a href="{{ route('super.empresas.create') }}" class="block w-full bg-white/20 hover:bg-white/30 px-3 py-2 rounded text-sm">
                    <i class="ri-add-line"></i> Cadastrar nova empresa
                </a>
                <a href="{{ route('super.users.create') }}" class="block w-full bg-white/20 hover:bg-white/30 px-3 py-2 rounded text-sm">
                    <i class="ri-user-add-line"></i> Criar usuário admin
                </a>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('chartGlobal'), {
    type: 'line',
    data: {
        labels: @json($vendasUltimos30Dias->pluck('dia')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
        datasets: [{
            label: 'Vendas (R$)',
            data: @json($vendasUltimos30Dias->pluck('total')),
            borderColor: '#f43f5e',
            backgroundColor: 'rgba(244,63,94,0.15)',
            borderWidth: 2,
            tension: 0.35,
            fill: true,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
});

const mrrMensal = @json($mrrMensal);
new Chart(document.getElementById('chart-mrr'), {
    type: 'line',
    data: {
        labels: mrrMensal.map(m => {
            const [, mes] = m.mes.split('-');
            const nomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            return nomes[parseInt(mes, 10) - 1];
        }),
        datasets: [{
            label: 'R$',
            data: mrrMensal.map(m => m.total),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.15)',
            borderWidth: 2, tension: 0.35, fill: true, pointRadius: 3,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true }}}
});

const novasMensal = @json($novasMensal);
new Chart(document.getElementById('chart-novas'), {
    type: 'bar',
    data: {
        labels: novasMensal.map(m => {
            const [, mes] = m.mes.split('-');
            const nomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            return nomes[parseInt(mes, 10) - 1];
        }),
        datasets: [{
            data: novasMensal.map(m => m.total),
            backgroundColor: '#6366f1',
            borderRadius: 6,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false }}, scales: { y: { beginAtZero: true, ticks: { precision: 0 }}}}
});

const distEl = document.getElementById('chart-planos');
if (distEl) {
    const dist = @json($distribuicaoPlanos);
    const cores = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#a855f7', '#3b82f6'];
    new Chart(distEl, {
        type: 'doughnut',
        data: {
            labels: dist.map(p => p.nome),
            datasets: [{
                data: dist.map(p => p.total),
                backgroundColor: dist.map((_, i) => cores[i % cores.length]),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: { responsive: true, cutout: '60%', plugins: { legend: { display: false }}}
    });
}
</script>
@endsection
