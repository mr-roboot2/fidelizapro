@extends('layouts.admin')
@section('title', $beneficio->exists ? 'Editar benefício' : 'Novo benefício')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <p class="text-sm text-slate-600 mb-4">
        Parceiro: <strong>{{ $parceiro->nome }}</strong>
    </p>

    <form method="POST"
          action="{{ $beneficio->exists ? route('admin.beneficios.update', $beneficio) : route('admin.beneficios.store', $parceiro) }}">
        @csrf
        @if ($beneficio->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome do benefício *</label>
                <input type="text" name="nome" required value="{{ old('nome', $beneficio->nome) }}"
                       placeholder="Ex: 10% desconto na conta"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Tipo *</label>
                <select name="tipo" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    @foreach (\App\Models\Beneficio::TIPOS as $v => $r)
                        <option value="{{ $v }}" @selected(old('tipo', $beneficio->tipo) === $v)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Valor (% ou R$)</label>
                <input type="number" name="valor" step="0.01" min="0" value="{{ old('valor', $beneficio->valor) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-xs text-slate-500 mt-1">Use só se tipo for desconto</p>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Descrição</label>
                <textarea name="descricao" rows="2" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">{{ old('descricao', $beneficio->descricao) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Condições / regras</label>
                <textarea name="condicoes" rows="3" placeholder="Ex: Não cumulativo. Apresentar cupom antes de pedir a conta."
                          class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">{{ old('condicoes', $beneficio->condicoes) }}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium">Válido até</label>
                <input type="date" name="valido_ate" value="{{ old('valido_ate', $beneficio->valido_ate?->toDateString()) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Limite por cliente</label>
                <input type="number" name="limite_por_cliente" min="1" value="{{ old('limite_por_cliente', $beneficio->limite_por_cliente) }}"
                       placeholder="vazio = sem limite"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Limite total (estoque)</label>
                <input type="number" name="limite_total" min="1" value="{{ old('limite_total', $beneficio->limite_total) }}"
                       placeholder="vazio = ilimitado"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="destaque" value="1" {{ old('destaque', $beneficio->destaque) ? 'checked' : '' }} class="rounded">
                <span class="text-sm">Destaque</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $beneficio->ativo ?? true) ? 'checked' : '' }} class="rounded">
                <span class="text-sm">Ativo</span>
            </label>
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('admin.parceiros.show', $parceiro) }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
