@extends("layouts.super")
@section('title', 'Campanhas WhatsApp')
@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('super.campanhas.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
        <i class="ri-add-line"></i> Nova campanha
    </a>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    @forelse ($campanhas as $c)
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between mb-2">
                <h3 class="font-semibold">{{ $c->nome }}</h3>
                <span @class([
                    'text-xs px-2 py-0.5 rounded-full',
                    'bg-slate-100' => $c->status === 'rascunho',
                    'bg-blue-100 text-blue-700' => $c->status === 'agendada',
                    'bg-amber-100 text-amber-700' => $c->status === 'enviando',
                    'bg-emerald-100 text-emerald-700' => $c->status === 'concluida',
                    'bg-rose-100 text-rose-700' => $c->status === 'falhou',
                ])>{{ ucfirst($c->status) }}</span>
            </div>
            <p class="text-xs text-slate-500 mb-2">
                <i class="ri-whatsapp-line"></i> Segmento: {{ $c->segmento }}
                @if ($c->agendada_para) • Agenda: {{ $c->agendada_para->format('d/m H:i') }} @endif
            </p>
            <p class="text-sm text-slate-700 line-clamp-3">{{ $c->mensagem }}</p>

            @if ($c->total_destinatarios > 0)
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="bg-slate-50 rounded p-2">
                        <p class="text-xs text-slate-500">Destinatários</p>
                        <p class="font-bold">{{ $c->total_destinatarios }}</p>
                    </div>
                    <div class="bg-emerald-50 rounded p-2">
                        <p class="text-xs text-emerald-700">Enviados</p>
                        <p class="font-bold text-emerald-700">{{ $c->total_enviados }}</p>
                    </div>
                    <div class="bg-rose-50 rounded p-2">
                        <p class="text-xs text-rose-700">Falhas</p>
                        <p class="font-bold text-rose-700">{{ $c->total_falhas }}</p>
                    </div>
                </div>
            @endif

            <div class="mt-3 flex gap-2">
                <a href="{{ route('super.campanhas.edit', $c) }}" class="flex-1 text-center text-sm py-1.5 bg-slate-100 rounded">Editar</a>
                @if (in_array($c->status, ['rascunho', 'agendada']))
                    <form action="{{ route('super.campanhas.disparar', $c) }}" method="POST" class="flex-1" onsubmit="return confirm('Disparar agora?')">
                        @csrf
                        <button class="w-full text-sm py-1.5 bg-emerald-600 text-white rounded">
                            <i class="ri-send-plane-line"></i> Disparar
                        </button>
                    </form>
                @endif
                <form action="{{ route('super.campanhas.destroy', $c) }}" method="POST" onsubmit="return confirm('Excluir?')">
                    @csrf @method('DELETE')
                    <button class="text-sm py-1.5 px-3 bg-rose-100 text-rose-700 rounded">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-slate-400 col-span-full text-center py-10">Nenhuma campanha criada.</p>
    @endforelse
</div>
<div class="mt-4">{{ $campanhas->links() }}</div>
@endsection
