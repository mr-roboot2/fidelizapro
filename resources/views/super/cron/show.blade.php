@extends('layouts.super')
@section('title', 'Execução #'.$execucao->id)
@section('content')

<div class="max-w-4xl">
    <a href="{{ route('super.cron.index') }}" class="text-sm text-indigo-600 hover:underline">
        <i class="ri-arrow-left-line"></i> Voltar
    </a>

    <div class="bg-white rounded-xl shadow-sm p-6 mt-4">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Execução #{{ $execucao->id }}</p>
                <p class="text-2xl font-bold font-mono mt-1">{{ $execucao->comando }}</p>
                <p class="text-sm text-slate-500">{{ \App\Models\CronExecucao::COMANDOS_MONITORADOS[$execucao->comando] ?? '' }}</p>
            </div>
            <div>
                @if ($execucao->status === 'sucesso')
                    <span class="text-sm px-3 py-1 rounded-full font-semibold bg-emerald-100 text-emerald-700">
                        <i class="ri-checkbox-circle-fill"></i> Sucesso
                    </span>
                @elseif ($execucao->status === 'falhou')
                    <span class="text-sm px-3 py-1 rounded-full font-semibold bg-rose-100 text-rose-700">
                        <i class="ri-close-circle-fill"></i> Falhou
                    </span>
                @else
                    <span class="text-sm px-3 py-1 rounded-full font-semibold bg-blue-100 text-blue-700">
                        <i class="ri-loader-line"></i> Rodando
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5 text-sm">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Iniciado</p>
                <p class="font-medium">{{ $execucao->iniciado_em->format('d/m/Y H:i:s') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Terminado</p>
                <p class="font-medium">{{ $execucao->terminado_em?->format('d/m/Y H:i:s') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Duração</p>
                <p class="font-medium">{{ $execucao->duracao_ms !== null ? $execucao->duracao_ms.' ms' : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider">Exit code</p>
                <p class="font-mono font-medium">{{ $execucao->exit_code ?? '—' }}</p>
            </div>
        </div>

        <div class="mt-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider mb-2">Output</p>
            <pre class="bg-slate-900 text-slate-100 text-xs p-4 rounded-lg overflow-x-auto whitespace-pre-wrap break-words max-h-96">{{ $execucao->output ?: '(sem output capturado)' }}</pre>
        </div>

        @if ($execucao->erro)
            <div class="mt-4">
                <p class="text-xs text-rose-500 uppercase tracking-wider mb-2">Erro</p>
                <pre class="bg-rose-50 text-rose-900 text-xs p-4 rounded-lg overflow-x-auto whitespace-pre-wrap">{{ $execucao->erro }}</pre>
            </div>
        @endif
    </div>
</div>
@endsection
