@extends('layouts.admin')
@section('title', $sorteio->exists ? 'Editar sorteio' : 'Novo sorteio')
@section('content')

<form method="POST" action="{{ $sorteio->exists ? route('admin.sorteios.update', $sorteio) : route('admin.sorteios.store') }}"
      enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-5 space-y-4 max-w-3xl">
    @csrf
    @if ($sorteio->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div>
        <label class="text-xs text-slate-600">Nome</label>
        <input name="nome" value="{{ old('nome', $sorteio->nome) }}" required maxlength="120" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="text-xs text-slate-600">Descrição</label>
        <textarea name="descricao" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('descricao', $sorteio->descricao) }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs text-slate-600">Data do sorteio</label>
            <input type="date" name="data_sorteio" value="{{ old('data_sorteio', $sorteio->data_sorteio?->format('Y-m-d')) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs text-slate-600">Status</label>
            <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach (\App\Models\Sorteio::STATUS as $k => $v)
                    <option value="{{ $k }}" @selected(old('status', $sorteio->status ?? 'planejado') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs text-slate-600">Prêmio (recompensa do catálogo)</label>
            <select name="recompensa_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">— ou usar valor estimado abaixo —</option>
                @foreach ($recompensas as $r)
                    <option value="{{ $r->id }}" @selected(old('recompensa_id', $sorteio->recompensa_id) == $r->id)>{{ $r->nome }}</option>
                @endforeach
            </select>
            <p class="text-[10px] text-slate-400 mt-1">Quando o vencedor é sorteado, um resgate aprovado é criado automaticamente.</p>
        </div>
        <div>
            <label class="text-xs text-slate-600">Valor estimado (R$, se sem recompensa)</label>
            <input type="number" step="0.01" name="valor_estimado" value="{{ old('valor_estimado', $sorteio->valor_estimado) }}" min="0" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs text-slate-600">Limite de bilhetes por cliente (vazio = sem limite)</label>
            <input type="number" name="max_bilhetes_por_cliente" value="{{ old('max_bilhetes_por_cliente', $sorteio->max_bilhetes_por_cliente) }}" min="1" max="1000" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs text-slate-600">Antifraude: limite por IP/dia</label>
            <input type="number" name="limite_bilhetes_dia_por_ip" value="{{ old('limite_bilhetes_dia_por_ip', $sorteio->limite_bilhetes_dia_por_ip) }}" min="1" max="200" placeholder="vazio = sem limite" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div>
        <label class="text-xs text-slate-600">Imagem (opcional)</label>
        <input type="file" name="imagem" accept="image/*" class="w-full text-sm">
        @if ($sorteio->imagem)
            <img src="{{ asset('storage/'.$sorteio->imagem) }}" class="h-16 mt-2 rounded">
        @endif
    </div>

    <div class="flex justify-end gap-2 border-t pt-4">
        <a href="{{ route('admin.sorteios.index') }}" class="px-4 py-2 bg-slate-100 rounded-lg text-sm">Cancelar</a>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Salvar</button>
    </div>
</form>
@endsection
