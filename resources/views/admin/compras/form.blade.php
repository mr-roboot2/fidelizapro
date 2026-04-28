@extends('layouts.admin')
@section('title', 'Lançar compra')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-xl">
    <form method="POST" action="{{ route('admin.compras.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="text-sm font-medium text-slate-700">Cliente *</label>
            <select name="cliente_id" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <option value="">Selecione...</option>
                @foreach ($clientes as $c)
                    <option value="{{ $c->id }}" {{ $clienteSelecionado == $c->id ? 'selected' : '' }}>
                        {{ $c->nome }} — {{ $c->telefone }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-slate-700">Valor da compra (R$) *</label>
                <input type="number" name="valor" required step="0.01" min="0.01"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Desconto (R$)</label>
                <input type="number" name="desconto" step="0.01" min="0" value="0"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Código (opcional)</label>
            <input type="text" name="codigo" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Descrição</label>
            <input type="text" name="descricao" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
        </div>
        <div class="flex gap-2 pt-2">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="ri-save-line"></i> Registrar compra
            </button>
            <a href="{{ route('admin.compras.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
