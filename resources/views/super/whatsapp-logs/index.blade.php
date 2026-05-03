@extends('layouts.super')
@section('title', 'Logs de WhatsApp')
@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-emerald-500">
        <p class="text-xs text-slate-500 uppercase tracking-wider">Total enviado</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($resumo['total'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
        <p class="text-xs text-slate-500 uppercase tracking-wider">Hoje</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($resumo['hoje'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-rose-500">
        <p class="text-xs text-slate-500 uppercase tracking-wider">Falhas (7d)</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($resumo['falhas'], 0, ',', '.') }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm">
    <form method="GET" class="p-4 border-b border-slate-200 grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
        <div class="md:col-span-2">
            <label class="text-xs text-slate-500">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Telefone, nome ou trecho da msg..."
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="text-xs text-slate-500">Empresa</label>
            <select name="empresa_id" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-sm">
                <option value="">Todas</option>
                @foreach ($empresas as $e)
                    <option value="{{ $e->id }}" @selected(request('empresa_id') == $e->id)>{{ $e->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Evento</label>
            <select name="evento" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-sm">
                <option value="">Todos</option>
                @foreach ($eventos as $ev)
                    <option value="{{ $ev }}" @selected(request('evento') === $ev)>{{ $ev }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Origem</label>
            <select name="origem" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-sm">
                <option value="">Todas</option>
                @foreach ($origens as $o)
                    <option value="{{ $o }}" @selected(request('origem') === $o)>{{ $o }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Status</label>
            <select name="status" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-sm">
                <option value="">Todos</option>
                <option value="ok" @selected(request('status') === 'ok')>Enviados</option>
                <option value="erro" @selected(request('status') === 'erro')>Falhas</option>
            </select>
        </div>
        <div class="md:col-span-6 flex justify-end gap-2">
            <a href="{{ route('super.whatsapp-logs.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">Limpar</a>
            <button class="px-4 py-2 bg-rose-600 text-white text-sm rounded-lg hover:bg-rose-700">
                <i class="ri-filter-line"></i> Filtrar
            </button>
        </div>
    </form>

    @include('_shared.whatsapp-logs-tabela', ['envios' => $envios, 'comEmpresa' => true])

    <div class="p-4">{{ $envios->links() }}</div>
</div>

@endsection
