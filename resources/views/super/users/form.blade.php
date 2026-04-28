@extends('layouts.super')
@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ $user->exists ? route('super.users.update', $user) : route('super.users.store') }}">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="name" required value="{{ old('name', $user->name) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">E-mail *</label>
                <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Senha {{ $user->exists ? '(deixe em branco para manter)' : '*' }}</label>
                <input type="text" name="password" {{ $user->exists ? '' : 'required' }} minlength="6"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Papel *</label>
                <select name="role" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    @foreach (['super_admin' => 'Super Admin (sem empresa)', 'admin' => 'Admin da empresa', 'gerente' => 'Gerente', 'atendente' => 'Atendente'] as $v => $r)
                        <option value="{{ $v }}" @selected(old('role', $user->role) === $v)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Empresa (deixe vazio para super admin)</label>
                <select name="empresa_id" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="">— sem empresa —</option>
                    @foreach ($empresas as $e)
                        <option value="{{ $e->id }}"
                                @selected(old('empresa_id', $user->empresa_id ?? request('empresa_id')) == $e->id)>
                            {{ $e->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($user->exists)
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $user->ativo) ? 'checked' : '' }} class="rounded">
                    <span class="text-sm">Usuário ativo</span>
                </label>
            @endif
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">Salvar</button>
            <a href="{{ route('super.users.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
