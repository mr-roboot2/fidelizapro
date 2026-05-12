@extends('layouts.super')
@section('title', 'Tarefas agendadas (cron)')
@section('content')

@if (session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">
        <i class="ri-check-line"></i> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg">
        <i class="ri-error-warning-line"></i> {{ session('error') }}
    </div>
@endif

<div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5 text-sm text-indigo-900">
    <p class="font-semibold mb-1"><i class="ri-information-line"></i> Como o cron funciona</p>
    <p class="text-indigo-800">
        O Laravel executa as tarefas agendadas via <code class="bg-white px-1 rounded">php artisan schedule:run</code>,
        chamado a cada minuto pelo cron do sistema operacional. Os horários reais de cada tarefa estão definidos
        em <code class="bg-white px-1 rounded">routes/console.php</code>. Cada execução é registrada aqui com tempo,
        status e output capturado.
    </p>
</div>

<h2 class="text-lg font-semibold text-slate-700 mb-3">Comandos monitorados</h2>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
    @foreach ($comandos as $cmd => $descricao)
        @php
            $u = $ultimas[$cmd] ?? null;
            $stats = $estatisticas[$cmd];
        @endphp
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800">{{ $descricao }}</p>
                    <p class="text-xs font-mono text-slate-400 mt-0.5">{{ $cmd }}</p>
                </div>
                <form action="{{ route('super.cron.executar', $cmd) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold whitespace-nowrap"
                            onclick="this.disabled = true; this.innerHTML = '<i class=\'ri-loader-line\'></i> Executando...'; this.form.submit()">
                        <i class="ri-play-line"></i> Executar agora
                    </button>
                </form>
            </div>

            @if ($u)
                <div class="flex items-center gap-3 text-sm border-t pt-3">
                    @if ($u->status === 'sucesso')
                        <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">
                            <i class="ri-checkbox-circle-fill"></i> Sucesso
                        </span>
                    @elseif ($u->status === 'falhou')
                        <span class="px-2 py-0.5 rounded-full text-xs bg-rose-100 text-rose-700">
                            <i class="ri-close-circle-fill"></i> Falhou
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">
                            <i class="ri-loader-line animate-spin"></i> Rodando
                        </span>
                    @endif
                    <span class="text-xs text-slate-500">
                        última: {{ $u->iniciado_em->format('d/m/Y H:i') }}
                        @if ($u->duracao_ms) · {{ $u->duracao_ms }}ms @endif
                    </span>
                    <a href="{{ route('super.cron.show', $u) }}" class="text-xs text-indigo-600 hover:underline ml-auto">Ver output</a>
                </div>
            @else
                <p class="text-sm text-slate-400 border-t pt-3"><i class="ri-time-line"></i> Nunca executado</p>
            @endif

            <div class="grid grid-cols-3 gap-2 mt-3 text-xs text-center">
                <div class="bg-slate-50 rounded p-2">
                    <p class="text-slate-500">Total 30d</p>
                    <p class="font-bold text-slate-700">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-emerald-50 rounded p-2">
                    <p class="text-emerald-700">Sucesso</p>
                    <p class="font-bold text-emerald-700">{{ $stats['sucesso'] }}</p>
                </div>
                <div class="bg-rose-50 rounded p-2">
                    <p class="text-rose-700">Falhas</p>
                    <p class="font-bold text-rose-700">{{ $stats['falhou'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<h2 class="text-lg font-semibold text-slate-700 mb-3">Histórico (últimas 100)</h2>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr class="text-left">
                <th class="p-3">Comando</th>
                <th>Iniciado em</th>
                <th>Duração</th>
                <th>Origem</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($historico as $h)
                <tr class="hover:bg-slate-50">
                    <td class="p-3 font-mono text-xs">{{ $h->comando }}</td>
                    <td class="text-xs">{{ $h->iniciado_em->format('d/m/Y H:i:s') }}</td>
                    <td class="text-xs">{{ $h->duracao_ms !== null ? $h->duracao_ms.'ms' : '—' }}</td>
                    <td class="text-xs">
                        <span class="px-1.5 py-0.5 rounded {{ $h->origem === 'manual' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $h->origem }}
                        </span>
                    </td>
                    <td>
                        @if ($h->status === 'sucesso')
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Sucesso</span>
                        @elseif ($h->status === 'falhou')
                            <span class="text-xs px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Falhou</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Rodando</span>
                        @endif
                    </td>
                    <td class="text-right pr-3">
                        <a href="{{ route('super.cron.show', $h) }}" class="text-xs text-indigo-600 hover:underline">Ver</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-400">Nenhuma execução registrada ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
