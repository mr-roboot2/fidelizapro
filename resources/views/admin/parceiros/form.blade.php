@extends('layouts.admin')
@section('title', $parceiro->exists ? 'Editar parceiro' : 'Novo parceiro')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST"
          action="{{ $parceiro->exists ? route('admin.parceiros.update', $parceiro) : route('admin.parceiros.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($parceiro->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome do parceiro *</label>
                <input type="text" name="nome" required value="{{ old('nome', $parceiro->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Categoria</label>
                <input type="text" name="categoria" value="{{ old('categoria', $parceiro->categoria) }}"
                       placeholder="Ex: Restaurante, Loja, Clínica"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Telefone</label>
                <input type="text" name="telefone" value="{{ old('telefone', $parceiro->telefone) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $parceiro->email) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Site</label>
                <input type="url" name="site" value="{{ old('site', $parceiro->site) }}" placeholder="https://..."
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Endereço</label>
                <input type="text" name="endereco" value="{{ old('endereco', $parceiro->endereco) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Descrição</label>
                <textarea name="descricao" rows="3" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">{{ old('descricao', $parceiro->descricao) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Logo</label>
                @if ($parceiro->logo)
                    <img src="{{ asset('storage/'.$parceiro->logo) }}" class="h-16 my-2">
                @endif
                <input type="file" name="logo" accept="image/*"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            @if ($parceiro->exists)
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $parceiro->ativo) ? 'checked' : '' }} class="rounded">
                    <span class="text-sm">Parceiro ativo</span>
                </label>
            @endif
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('admin.parceiros.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
