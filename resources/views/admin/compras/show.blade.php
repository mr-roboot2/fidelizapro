@extends('layouts.admin')
@section('title', 'Compra #'.$compra->id)
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <h2 class="font-bold text-lg mb-4">Compra #{{ $compra->id }}</h2>
    <dl class="space-y-2 text-sm">
        <div class="flex justify-between"><dt class="text-slate-500">Cliente</dt><dd>{{ $compra->cliente->nome }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Data</dt><dd>{{ $compra->created_at->format('d/m/Y H:i') }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Valor</dt><dd>R$ {{ number_format($compra->valor, 2, ',', '.') }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Pontos gerados</dt><dd class="text-amber-600 font-semibold">+{{ number_format($compra->pontos_gerados, 0, ',', '.') }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Cashback</dt><dd>R$ {{ number_format($compra->cashback_gerado, 2, ',', '.') }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Origem</dt><dd>{{ $compra->origem }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Atendente</dt><dd>{{ $compra->user?->name ?? '—' }}</dd></div>
    </dl>
</div>
@endsection
