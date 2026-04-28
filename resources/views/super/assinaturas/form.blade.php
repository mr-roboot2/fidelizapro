@extends('layouts.super')
@section('title', 'Nova assinatura')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ route('super.assinaturas.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium">Empresa *</label>
                <select name="empresa_id" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="">Selecione...</option>
                    @foreach ($empresas as $e)
                        <option value="{{ $e->id }}">{{ $e->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Plano *</label>
                <select name="plano_id" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    @foreach ($planos as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }} — R$ {{ number_format($p->preco_mensal, 2, ',', '.') }}/mês</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Gateway *</label>
                <select name="gateway" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="mock">Mock (dev — gera link fake)</option>
                    <option value="asaas">Asaas (produção)</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Dias de trial</label>
                <input type="number" name="dias_trial" value="7" min="0" max="60"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-xs text-slate-500 mt-1">0 = ativa imediato. Padrão: 7 dias.</p>
            </div>
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-rose-600 text-white rounded-lg">Criar assinatura</button>
            <a href="{{ route('super.assinaturas.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
