@extends('layouts.admin')
@section('title', 'Configurações da empresa')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <form method="POST" action="{{ route('admin.configuracoes.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <h3 class="font-semibold mb-4 text-slate-700">Dados da empresa</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $empresa->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
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
            <div>
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
                <input type="color" name="cor_primaria" value="{{ old('cor_primaria', $empresa->cor_primaria) }}"
                       class="mt-1 w-full h-12 border border-slate-300 rounded-lg cursor-pointer">
            </div>
            <div>
                <label class="text-sm font-medium">Cor secundária</label>
                <input type="color" name="cor_secundaria" value="{{ old('cor_secundaria', $empresa->cor_secundaria) }}"
                       class="mt-1 w-full h-12 border border-slate-300 rounded-lg cursor-pointer">
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-4 text-slate-700">Programa de fidelidade</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium">Pontos por R$ 1,00 *</label>
                <input type="number" name="pontos_por_real" required step="0.01" min="0"
                       value="{{ old('pontos_por_real', $empresa->pontos_por_real) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Cashback (%) *</label>
                <input type="number" name="cashback_percentual" required step="0.01" min="0" max="100"
                       value="{{ old('cashback_percentual', $empresa->cashback_percentual) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Validade pontos (dias) *</label>
                <input type="number" name="validade_pontos_dias" required min="30"
                       value="{{ old('validade_pontos_dias', $empresa->validade_pontos_dias) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Dias para liberar cashback *</label>
                <input type="number" name="dias_liberar_cashback" required min="0" max="365"
                       value="{{ old('dias_liberar_cashback', $empresa->dias_liberar_cashback ?? 0) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-xs text-slate-500 mt-1">0 = libera imediato. Maior = fica pendente até confirmação (ex: 30 dias).</p>
            </div>
        </div>

        <button class="mt-6 px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar configurações</button>
    </form>

    <div class="mt-8 p-4 bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl">
        <h3 class="font-semibold text-slate-700 mb-2">
            <i class="ri-smartphone-line"></i> Link white label para clientes
        </h3>
        <p class="text-sm text-slate-600 mb-3">
            Compartilhe este link — quando o cliente abrir, verá a PWA com o nome, logo e cores da sua empresa, instalável como app.
        </p>
        <div class="flex">
            <code class="flex-1 px-3 py-2 bg-white rounded-l border border-slate-200 text-sm break-all">{{ url('/app/'.$empresa->slug.'/') }}</code>
            <button onclick="navigator.clipboard.writeText('{{ url('/app/'.$empresa->slug.'/') }}'); this.textContent='✓'"
                    class="px-3 bg-indigo-600 text-white rounded-r text-sm">Copiar</button>
        </div>
        <a href="{{ url('/app/'.$empresa->slug.'/') }}" target="_blank" class="text-xs text-indigo-600 mt-2 inline-block">
            <i class="ri-external-link-line"></i> Abrir em nova aba
        </a>
    </div>
</div>
@endsection
