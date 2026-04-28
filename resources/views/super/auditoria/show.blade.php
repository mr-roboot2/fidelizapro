@extends('layouts.super')
@section('title', 'Log #'.$log->id)
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <a href="{{ route('super.auditoria.index') }}" class="text-sm text-slate-500 mb-4 inline-block">← Voltar</a>

    <h2 class="font-bold text-lg mb-4">{{ $log->descricao }}</h2>

    <dl class="grid grid-cols-2 gap-4 text-sm mb-6">
        <div><dt class="text-slate-500">Data/hora</dt><dd>{{ $log->created_at->format('d/m/Y H:i:s') }}</dd></div>
        <div><dt class="text-slate-500">Ação</dt><dd>{{ $log->acao }}</dd></div>
        <div><dt class="text-slate-500">Usuário</dt><dd>{{ $log->user?->name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Empresa</dt><dd>{{ $log->empresa?->nome ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Entidade</dt><dd>{{ $log->entidadeNomeCurto() }} #{{ $log->entidade_id }}</dd></div>
        <div><dt class="text-slate-500">IP</dt><dd class="font-mono">{{ $log->ip }}</dd></div>
        <div class="col-span-2"><dt class="text-slate-500">User-Agent</dt><dd class="text-xs">{{ $log->user_agent }}</dd></div>
    </dl>

    @if ($log->antes)
        <div class="mb-4">
            <h3 class="font-semibold text-rose-700 mb-2">📤 Antes</h3>
            <pre class="bg-rose-50 border border-rose-200 p-3 rounded text-xs overflow-x-auto">{{ json_encode($log->antes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif

    @if ($log->depois)
        <div class="mb-4">
            <h3 class="font-semibold text-emerald-700 mb-2">📥 Depois</h3>
            <pre class="bg-emerald-50 border border-emerald-200 p-3 rounded text-xs overflow-x-auto">{{ json_encode($log->depois, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
@endsection
