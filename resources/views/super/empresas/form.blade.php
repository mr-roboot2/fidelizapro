@extends('layouts.super')
@section('title', $empresa->exists ? 'Editar empresa' : 'Nova empresa')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <form method="POST"
          action="{{ $empresa->exists ? route('super.empresas.update', $empresa) : route('super.empresas.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($empresa->exists) @method('PUT') @endif

        <h3 class="font-semibold mb-4 text-slate-700">Dados da empresa</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $empresa->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Slug (URL)</label>
                <input type="text" name="slug" value="{{ old('slug', $empresa->slug) }}"
                       placeholder="gerado automaticamente"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
            </div>
            <div>
                <label class="text-sm font-medium">CNPJ</label>
                <input type="text" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Telefone</label>
                <input type="text" name="telefone" value="{{ old('telefone', $empresa->telefone) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $empresa->email) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Endereço</label>
                <input type="text" name="endereco" value="{{ old('endereco', $empresa->endereco) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Logo</label>
                @if ($empresa->logo)
                    <img src="{{ asset('storage/'.$empresa->logo) }}" class="h-16 my-2">
                @endif
                <input type="file" name="logo" accept="image/*"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-4 text-slate-700">Identidade visual</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Cor primária</label>
                <input type="color" name="cor_primaria" value="{{ old('cor_primaria', $empresa->cor_primaria ?? '#6366f1') }}"
                       class="mt-1 w-full h-12 border border-slate-300 rounded-lg cursor-pointer">
            </div>
            <div>
                <label class="text-sm font-medium">Cor secundária</label>
                <input type="color" name="cor_secundaria" value="{{ old('cor_secundaria', $empresa->cor_secundaria ?? '#8b5cf6') }}"
                       class="mt-1 w-full h-12 border border-slate-300 rounded-lg cursor-pointer">
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-4 text-slate-700">Programa de fidelidade</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium">Pontos por R$ 1 *</label>
                <input type="number" name="pontos_por_real" required step="0.01" min="0"
                       value="{{ old('pontos_por_real', $empresa->pontos_por_real ?? 1) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Cashback (%) *</label>
                <input type="number" name="cashback_percentual" required step="0.01" min="0" max="100"
                       value="{{ old('cashback_percentual', $empresa->cashback_percentual ?? 0) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Validade pontos (dias) *</label>
                <input type="number" name="validade_pontos_dias" required min="30"
                       value="{{ old('validade_pontos_dias', $empresa->validade_pontos_dias ?? 365) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>

        @if ($empresa->exists)
            <h3 class="font-semibold mt-6 mb-1 text-slate-700">Antifraude e limites</h3>
            <p class="text-xs text-slate-500 mb-4">
                Controles definidos pelo super admin. Os admins da empresa só visualizam — quem ajusta é você.
                Valores muito altos facilitam ataques, muito baixos atrapalham clientes legítimos.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium">Login/Registro/OTP por minuto</label>
                    <input type="number" name="rate_limit_auth" min="1" max="1000"
                           value="{{ old('rate_limit_auth', $empresa->rate_limit_auth ?: 10) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <p class="text-xs text-slate-500 mt-1">Tentativas por IP/min. Padrão: 10.</p>
                </div>
                <div>
                    <label class="text-sm font-medium">Webhook PDV por minuto</label>
                    <input type="number" name="rate_limit_pdv" min="1" max="5000"
                           value="{{ old('rate_limit_pdv', $empresa->rate_limit_pdv ?: 60) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <p class="text-xs text-slate-500 mt-1">Lançamentos PDV por IP/min. Padrão: 60.</p>
                </div>
                <div>
                    <label class="text-sm font-medium">OTPs por telefone (15 min)</label>
                    <input type="number" name="otp_max_por_telefone" min="1" max="50"
                           value="{{ old('otp_max_por_telefone', $empresa->otp_max_por_telefone ?: 3) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <p class="text-xs text-slate-500 mt-1">Códigos por telefone em 15 min. Padrão: 3.</p>
                </div>
                <div>
                    <label class="text-sm font-medium">Tentativas por código OTP</label>
                    <input type="number" name="otp_max_tentativas" min="1" max="50"
                           value="{{ old('otp_max_tentativas', $empresa->otp_max_tentativas ?: 5) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <p class="text-xs text-slate-500 mt-1">Erros antes de invalidar. Padrão: 5.</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium">Resgates por cliente em 24h</label>
                    <input type="number" name="max_resgates_24h" min="1" max="100"
                           value="{{ old('max_resgates_24h', $empresa->max_resgates_24h ?: 3) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <p class="text-xs text-slate-500 mt-1">Prêmios por cliente por dia. Padrão: 3.</p>
                </div>
            </div>
        @endif

        @if (!$empresa->exists)
            <h3 class="font-semibold mt-6 mb-4 text-slate-700">Admin inicial da empresa</h3>
            <p class="text-xs text-slate-500 mb-3">Esse usuário poderá gerenciar a empresa pelo painel admin.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium">Nome *</label>
                    <input type="text" name="admin_nome" required value="{{ old('admin_nome') }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="text-sm font-medium">E-mail *</label>
                    <input type="email" name="admin_email" required value="{{ old('admin_email') }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="text-sm font-medium">Senha *</label>
                    <input type="text" name="admin_password" required minlength="6"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>
        @else
            <label class="flex items-center gap-2 mt-4">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $empresa->ativo) ? 'checked' : '' }} class="rounded">
                <span class="text-sm">Empresa ativa</span>
            </label>
        @endif

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
                {{ $empresa->exists ? 'Salvar alterações' : 'Cadastrar empresa' }}
            </button>
            <a href="{{ route('super.empresas.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
