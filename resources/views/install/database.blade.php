@extends('install.layout', ['step' => 'database'])

@section('title', 'Banco de dados')

@section('content')
    <h2 class="text-xl font-bold text-slate-800 mb-1">Configuração do banco</h2>
    <p class="text-slate-500 text-sm mb-6">Informe as credenciais do MySQL/MariaDB. O instalador testa a conexão antes de salvar.</p>

    <form method="POST" action="{{ url('/install/database') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Host</label>
                <input type="text" name="db_host" value="{{ old('db_host', $db_host) }}" required
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Porta</label>
                <input type="number" name="db_port" value="{{ old('db_port', $db_port) }}" required
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome do banco</label>
            <input type="text" name="db_database" value="{{ old('db_database', $db_database) }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1">No CloudPanel: Sites &rarr; seu site &rarr; Databases &rarr; nome exato (sem prefixo).</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Usuário</label>
            <input type="text" name="db_username" value="{{ old('db_username', $db_username) }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
            <input type="password" name="db_password" value="{{ old('db_password') }}"
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="flex justify-between pt-2">
            <a href="{{ url('/install') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium text-slate-600 hover:text-slate-900">
                <i class="ri-arrow-left-line"></i> Voltar
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
                Testar e continuar <i class="ri-arrow-right-line"></i>
            </button>
        </div>
    </form>
@endsection
