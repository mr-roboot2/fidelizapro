@extends('layouts.admin')
@section('title', 'Resgate '.$resgate->codigo)
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <h2 class="font-bold text-lg mb-4">Resgate {{ $resgate->codigo }}</h2>
    <dl class="space-y-2 text-sm">
        <div class="flex justify-between"><dt class="text-slate-500">Cliente</dt><dd>{{ $resgate->cliente->nome }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Recompensa</dt><dd>{{ $resgate->recompensa->nome }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Pontos usados</dt><dd>{{ number_format($resgate->pontos_usados,0,',','.') }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd>{{ ucfirst($resgate->status) }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Solicitado em</dt><dd>{{ $resgate->created_at->format('d/m/Y H:i') }}</dd></div>
        @if ($resgate->aprovado_em)
            <div class="flex justify-between"><dt class="text-slate-500">Aprovado em</dt><dd>{{ $resgate->aprovado_em->format('d/m/Y H:i') }}</dd></div>
        @endif
        @if ($resgate->entregue_em)
            <div class="flex justify-between"><dt class="text-slate-500">Entregue em</dt><dd>{{ $resgate->entregue_em->format('d/m/Y H:i') }}</dd></div>
        @endif
        @if ($resgate->aprovador)
            <div class="flex justify-between"><dt class="text-slate-500">Aprovado por</dt><dd>{{ $resgate->aprovador->name }}</dd></div>
        @endif
    </dl>
    <div class="mt-6 flex gap-2">
        @if ($resgate->status === 'pendente')
            <form action="{{ route('admin.resgates.aprovar', $resgate) }}" method="POST">
                @csrf <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg">Aprovar</button>
            </form>
        @endif
        @if (in_array($resgate->status, ['aprovado', 'pendente']))
            <form action="{{ route('admin.resgates.entregar', $resgate) }}" method="POST">
                @csrf <button class="px-4 py-2 bg-blue-600 text-white rounded-lg">Marcar entregue</button>
            </form>
        @endif
    </div>
</div>
@endsection
