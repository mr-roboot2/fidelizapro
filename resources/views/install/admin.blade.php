@extends('install.layout', ['step' => 'admin'])

@section('title', 'Super Admin')

@section('content')
    <h2 class="text-xl font-bold text-slate-800 mb-1">Conta de super administrador</h2>
    <p class="text-slate-500 text-sm mb-6">É o usuário que gerencia o SaaS inteiro (todas as empresas, planos, assinaturas).</p>

    @if($existing)
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 mb-4 rounded-lg text-sm">
            <i class="ri-check-line"></i>
            Já existe um super admin: <strong>{{ $existing->email }}</strong>.
            Você pode pular esta etapa ou cadastrar uma conta adicional abaixo.
        </div>
        <div class="flex justify-end mb-6">
            <form method="POST" action="{{ url('/install/admin/skip') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
                    Usar este e finalizar <i class="ri-arrow-right-line"></i>
                </button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ url('/install/admin') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
            <input type="text" name="name" value="{{ old('name', 'Super Admin') }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar senha</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
        </div>

        <p class="text-xs text-slate-500">Mínimo 8 caracteres. Use letras, números e símbolos.</p>

        <div class="flex justify-end pt-2">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
                Criar e finalizar <i class="ri-arrow-right-line"></i>
            </button>
        </div>
    </form>
@endsection
