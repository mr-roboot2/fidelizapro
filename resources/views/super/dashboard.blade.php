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
</script>
@endsection
