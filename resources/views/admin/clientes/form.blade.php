@extends('layouts.admin')
@section('title', $cliente->exists ? 'Editar cliente' : 'Novo cliente')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}">
        @csrf
        @if ($cliente->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-slate-700">Nome completo *</label>
                <input type="text" name="nome" required value="{{ old('nome', $cliente->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Telefone *</label>
                <input type="text" name="telefone" required value="{{ old('telefone', $cliente->telefone) }}"
                       placeholder="(11) 99999-9999"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">CPF</label>
                <input type="text" name="cpf" value="{{ old('cpf', $cliente->cpf) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $cliente->email) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Data de nascimento</label>
                <input type="date" name="data_nascimento" value="{{ old('data_nascimento', $cliente->data_nascimento?->toDateString()) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <label class="flex items-center gap-2 sm:col-span-2 mt-2">
                <input type="checkbox" name="aceita_whatsapp" value="1"
                       {{ old('aceita_whatsapp', $cliente->aceita_whatsapp ?? true) ? 'checked' : '' }}
                       class="rounded">
                <span class="text-sm">Aceita receber WhatsApp</span>
            </label>
            @if ($cliente->exists)
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $cliente->ativo) ? 'checked' : '' }} class="rounded">
                    <span class="text-sm">Ativo</span>
                </label>
            @endif
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Salvar
            </button>
            <a href="{{ route('admin.clientes.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
        @if (!$cliente->exists)
            <p class="text-xs text-slate-500 mt-3">A senha inicial será os 6 últimos dígitos do telefone.</p>
        @endif
    </form>
</div>
@endsection
