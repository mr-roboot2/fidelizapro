@extends('layouts.admin')
@section('title', $campanha->exists ? 'Editar campanha' : 'Nova campanha')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ $campanha->exists ? route('admin.campanhas.update', $campanha) : route('admin.campanhas.store') }}">
        @csrf
        @if ($campanha->exists) @method('PUT') @endif
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $campanha->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium">Canal</label>
                    <select name="canal" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                        @foreach (['whatsapp'=>'WhatsApp','sms'=>'SMS','email'=>'E-mail'] as $v=>$r)
                            <option value="{{ $v }}" @selected(old('canal',$campanha->canal)===$v)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Segmento *</label>
                    <select name="segmento" required class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                        @foreach (['todos'=>'Todos clientes','aniversariantes'=>'Aniversariantes do mês','inativos'=>'Inativos (60+ dias)','vips'=>'VIPs (top 50)','sem_compra_30d'=>'Sem compra 30d'] as $v=>$r)
                            <option value="{{ $v }}" @selected(old('segmento',$campanha->segmento)===$v)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium">Mensagem *</label>
                <textarea name="mensagem" required rows="6" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">{{ old('mensagem', $campanha->mensagem) }}</textarea>
                <p class="text-xs text-slate-500 mt-1">Variáveis: <code>{nome}</code>, <code>{primeiro_nome}</code>, <code>{pontos}</code>, <code>{cashback}</code>, <code>{empresa}</code></p>
            </div>
            <div>
                <label class="text-sm font-medium">Agendar para (opcional)</label>
                <input type="datetime-local" name="agendada_para"
                       value="{{ old('agendada_para', $campanha->agendada_para?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>
        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('admin.campanhas.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
