@extends('layouts.admin')
@section('title', $regra->exists ? 'Editar regra' : 'Nova regra')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ $regra->exists ? route('admin.regras.update', $regra) : route('admin.regras.store') }}">
        @csrf
        @if ($regra->exists) @method('PUT') @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $regra->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Tipo *</label>
                <select name="tipo" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    @foreach (['compra' => 'Por compra', 'aniversario' => 'Aniversário', 'indicacao' => 'Indicação', 'primeira_compra' => 'Primeira compra', 'cadastro' => 'Cadastro', 'avaliacao' => 'Avaliação/Pesquisa'] as $v => $r)
                        <option value="{{ $v }}" {{ old('tipo', $regra->tipo) === $v ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Multiplicador</label>
                <input type="number" name="multiplicador" step="0.1" value="{{ old('multiplicador', $regra->multiplicador ?? 1) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Pontos por R$ (compra)</label>
                <input type="number" name="pontos_por_real" step="0.01" value="{{ old('pontos_por_real', $regra->pontos_por_real) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Pontos fixos (bônus)</label>
                <input type="number" name="pontos_fixos" value="{{ old('pontos_fixos', $regra->pontos_fixos) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Valor mínimo (R$)</label>
                <input type="number" name="valor_minimo" step="0.01" value="{{ old('valor_minimo', $regra->valor_minimo ?? 0) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Valor máximo (R$)</label>
                <input type="number" name="valor_maximo" step="0.01" value="{{ old('valor_maximo', $regra->valor_maximo) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Início</label>
                <input type="date" name="data_inicio" value="{{ old('data_inicio', $regra->data_inicio?->toDateString()) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Fim</label>
                <input type="date" name="data_fim" value="{{ old('data_fim', $regra->data_fim?->toDateString()) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <label class="flex items-center gap-2 sm:col-span-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $regra->ativo ?? true) ? 'checked' : '' }} class="rounded">
                <span class="text-sm">Regra ativa</span>
            </label>
        </div>
        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('admin.regras.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
