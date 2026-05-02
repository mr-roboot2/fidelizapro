@extends('install.layout', ['step' => 'app'])

@section('title', 'Aplicação')

@section('content')
    <h2 class="text-xl font-bold text-slate-800 mb-1">Configuração da aplicação</h2>
    <p class="text-slate-500 text-sm mb-6">Define a identidade do sistema e roda as migrations do banco.</p>

    <form method="POST" action="{{ url('/install/app') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nome da aplicação</label>
            <input type="text" name="app_name" value="{{ old('app_name', $app_name) }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">URL pública</label>
            <input type="url" name="app_url" value="{{ old('app_url', $app_url) }}" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1">Ex.: <code>https://satisfy.com.br</code> &mdash; usada para gerar links absolutos do PWA.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Fuso horário</label>
            <select name="app_timezone" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                @foreach(['America/Sao_Paulo','America/Manaus','America/Belem','America/Fortaleza','America/Recife','America/Cuiaba','America/Rio_Branco','UTC'] as $tz)
                    <option value="{{ $tz }}" @selected(old('app_timezone', $app_timezone) === $tz)>{{ $tz }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex items-start gap-3 p-4 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
            <input type="checkbox" name="seed" value="1" class="mt-1 rounded border-slate-300 text-indigo-600">
            <div>
                <div class="text-sm font-medium text-slate-800">Carregar dados de exemplo (recomendado para testes)</div>
                <div class="text-xs text-slate-500 mt-0.5">
                    Cria 3 empresas (padaria, salão, restaurante), 60 clientes, ~230 compras, 21 automações WhatsApp e o super admin <code>super@fidelizapro.com</code> / <code>password</code>.
                    <strong class="text-rose-600">Em produção real, desmarque.</strong>
                </div>
            </div>
        </label>

        <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-3 rounded-lg text-sm">
            <i class="ri-information-line"></i> Ao continuar, o instalador vai gerar a <code>APP_KEY</code> e rodar todas as migrations. Pode levar alguns segundos.
        </div>

        <div class="flex justify-between pt-2">
            <a href="{{ url('/install/database') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium text-slate-600 hover:text-slate-900">
                <i class="ri-arrow-left-line"></i> Voltar
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
                Rodar migrations <i class="ri-arrow-right-line"></i>
            </button>
        </div>
    </form>
@endsection
