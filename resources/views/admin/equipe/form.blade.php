@extends('layouts.admin')
@section('title', $usuario->exists ? 'Editar membro da equipe' : 'Novo membro da equipe')
@section('content')

<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ $usuario->exists ? route('admin.equipe.update', $usuario) : route('admin.equipe.store') }}">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <h3 class="font-semibold text-slate-800 mb-4">
            {{ $usuario->exists ? 'Editar '.$usuario->name : 'Cadastrar novo membro' }}
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-slate-700">Nome completo *</label>
                <input type="text" name="name" required maxlength="255"
                       value="{{ old('name', $usuario->name) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-slate-700">E-mail (usado pra login) *</label>
                <input type="email" name="email" required maxlength="255"
                       value="{{ old('email', $usuario->email) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-xs text-slate-500 mt-1">O membro vai usar esse e-mail pra entrar no PWA da loja ou no painel admin.</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">
                    Senha {{ $usuario->exists ? '(deixe em branco pra manter)' : '*' }}
                </label>
                <input type="password" name="password" minlength="8" autocomplete="new-password"
                       {{ $usuario->exists ? '' : 'required' }}
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono">
                <p class="text-xs text-slate-500 mt-1">Mínimo 8 caracteres.</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Função *</label>
                <select name="role" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    @foreach ($rolesPermitidas as $r)
                        @php
                            $rotulos = [
                                'gerente' => 'Gerente (acesso quase total)',
                                'atendente' => 'Atendente (caixa + clientes)',
                            ];
                        @endphp
                        <option value="{{ $r }}" @selected(old('role', $usuario->role) === $r)>
                            {{ $rotulos[$r] ?? $r }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($usuario->exists)
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="ativo" value="1" {{ old('ativo', $usuario->ativo) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-slate-700">Conta ativa</span>
                    </label>
                    <p class="text-xs text-slate-500 mt-1 ml-6">Desativar não exclui — derruba sessões e impede login até reativar.</p>
                </div>
            @endif
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mt-6 text-xs text-slate-600">
            <p class="font-semibold mb-1 text-slate-800"><i class="ri-information-line"></i> Diferenças entre as funções</p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>Atendente:</strong> opera o caixa rápido, vê clientes/compras, aprova resgates. Não mexe em config, regras, recompensas, parceiros, roleta, sorteio nem exclui clientes.</li>
                <li><strong>Gerente:</strong> mesmas permissões do administrador da loja (exceto excluir o próprio admin e cancelar a assinatura).</li>
            </ul>
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm">
                <i class="ri-save-line"></i> Salvar
            </button>
            <a href="{{ route('admin.equipe.index') }}" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 rounded-lg text-sm">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection
