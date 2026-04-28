@extends('layouts.admin')
@section('title', $recompensa->exists ? 'Editar recompensa' : 'Nova recompensa')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST"
          action="{{ $recompensa->exists ? route('admin.recompensas.update', $recompensa) : route('admin.recompensas.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($recompensa->exists) @method('PUT') @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $recompensa->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Descrição</label>
                <textarea name="descricao" rows="3" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">{{ old('descricao', $recompensa->descricao) }}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium">Custo em pontos *</label>
                <input type="number" name="custo_pontos" required min="1" value="{{ old('custo_pontos', $recompensa->custo_pontos) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Tipo *</label>
                <select name="tipo" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    @foreach (['produto'=>'Produto','desconto'=>'Desconto','servico'=>'Serviço','experiencia'=>'Experiência'] as $v=>$r)
                        <option value="{{ $v }}" {{ old('tipo', $recompensa->tipo) === $v ? 'selected':''}}>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Estoque (vazio = ilimitado)</label>
                <input type="number" name="estoque" min="0" value="{{ old('estoque', $recompensa->estoque) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Valor estimado (R$)</label>
                <input type="number" name="valor_estimado" step="0.01" value="{{ old('valor_estimado', $recompensa->valor_estimado) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Válido até</label>
                <input type="date" name="valido_ate" value="{{ old('valido_ate', $recompensa->valido_ate?->toDateString()) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Imagem</label>
                <input type="file" name="imagem" accept="image/*"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="destaque" value="1" {{ old('destaque', $recompensa->destaque) ? 'checked':'' }} class="rounded">
                <span class="text-sm">Destaque</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $recompensa->ativo ?? true) ? 'checked':'' }} class="rounded">
                <span class="text-sm">Ativo</span>
            </label>
        </div>
        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('admin.recompensas.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
