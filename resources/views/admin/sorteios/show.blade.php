@extends('layouts.admin')
@section('title', $sorteio->nome)
@section('content')

@if (session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm p-5 space-y-4">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold">{{ $sorteio->nome }}</h1>
            @if ($sorteio->descricao)
                <p class="text-sm text-slate-600 mt-1">{{ $sorteio->descricao }}</p>
            @endif
        </div>
        <span @class([
            'text-sm px-3 py-1 rounded-full font-semibold',
            'bg-emerald-100 text-emerald-700' => $sorteio->status === 'ativo',
            'bg-amber-100 text-amber-700' => $sorteio->status === 'planejado',
            'bg-indigo-100 text-indigo-700' => $sorteio->status === 'sorteado',
            'bg-slate-700 text-white' => $sorteio->status === 'finalizado',
            'bg-slate-200 text-slate-500' => $sorteio->status === 'cancelado',
        ])>{{ ucfirst($sorteio->status) }}</span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Data do sorteio</p>
            <p class="font-semibold text-slate-800">{{ $sorteio->data_sorteio->format('d/m/Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Bilhetes vendidos</p>
            <p class="font-semibold text-slate-800">{{ $bilhetes->total() }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Prêmio</p>
            <p class="font-semibold text-slate-800 text-xs">
                @if ($sorteio->recompensa_id) {{ $sorteio->recompensa->nome ?? '—' }}
                @elseif ($sorteio->valor_estimado) R$ {{ number_format($sorteio->valor_estimado, 2, ',', '.') }}
                @else — @endif
            </p>
        </div>
        <div>
            <p class="text-xs text-slate-500 uppercase tracking-wider">Limite/cliente</p>
            <p class="font-semibold text-slate-800">{{ $sorteio->max_bilhetes_por_cliente ?? '∞' }}</p>
        </div>
    </div>

    @if ($sorteio->status === 'sorteado' && $sorteio->vencedor)
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-3">
            <i class="ri-trophy-fill text-3xl text-amber-600"></i>
            <div>
                <p class="text-xs text-amber-700 uppercase tracking-wider">Vencedor</p>
                <p class="text-lg font-bold text-amber-900">{{ $sorteio->vencedor->nome }}</p>
                <p class="text-xs text-amber-700">{{ $sorteio->vencedor->telefone }} · sorteado em {{ $sorteio->sorteado_em->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-2 border-t pt-4">
        <a href="{{ route('admin.sorteios.edit', $sorteio) }}" class="px-3 py-1.5 bg-slate-100 rounded-lg text-sm">Editar</a>
        @if (in_array($sorteio->status, ['planejado', 'cancelado']))
            <form action="{{ route('admin.sorteios.ativar', $sorteio) }}" method="POST" class="inline">@csrf
                <button class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-sm">
                    <i class="ri-play-line"></i> Ativar (aceitar bilhetes)
                </button>
            </form>
        @endif
        @if ($sorteio->status === 'ativo')
            <form action="{{ route('admin.sorteios.sortear', $sorteio) }}" method="POST" class="inline"
                  onsubmit="return confirm('Sortear o vencedor agora? Essa ação é definitiva.')">@csrf
                <button class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm">
                    <i class="ri-trophy-line"></i> Sortear vencedor agora
                </button>
            </form>
            <form action="{{ route('admin.sorteios.cancelar', $sorteio) }}" method="POST" class="inline"
                  onsubmit="return confirm('Cancelar sorteio? Os bilhetes ficam invalidados.')">@csrf
                <button class="px-3 py-1.5 bg-rose-100 text-rose-700 rounded-lg text-sm">Cancelar sorteio</button>
            </form>
        @endif
        @if ($sorteio->status === 'sorteado')
            <form action="{{ route('admin.sorteios.finalizar', $sorteio) }}" method="POST" class="inline"
                  onsubmit="return confirm('Finalizar sorteio? Ele some do PWA dos clientes (mas fica no histórico do admin).')">@csrf
                <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm">
                    <i class="ri-archive-line"></i> Finalizar sorteio (arquivar)
                </button>
            </form>
        @endif
    </div>
</div>

@if ($sorteio->status === 'ativo')
<div class="bg-white rounded-xl shadow-sm p-5 mt-4" x-data="buscaCliente()">
    <h2 class="text-lg font-semibold mb-3">Creditar bilhetes manualmente</h2>
    <form action="{{ route('admin.sorteios.bilhetes', $sorteio) }}" method="POST"
          class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end"
          @submit="if (!selecionado) { $event.preventDefault(); alert('Selecione um cliente.'); }">
        @csrf
        <div class="md:col-span-2 relative">
            <label class="text-xs text-slate-600">Cliente</label>
            <input x-show="!selecionado" type="text" x-model="termo" @input.debounce.300ms="buscar()" @focus="if (resultados.length) aberto = true"
                   placeholder="Digite ao menos 3 caracteres…"
                   class="w-full border rounded-lg px-3 py-2 text-sm" autocomplete="off">
            <div x-show="selecionado" x-cloak class="w-full border rounded-lg px-3 py-2 text-sm bg-emerald-50 border-emerald-300 flex items-center gap-2">
                <i class="ri-check-line text-emerald-600"></i>
                <span class="flex-1 font-medium" x-text="selecionado?.nome"></span>
                <button type="button" @click="limpar()" class="text-xs text-rose-600">trocar</button>
            </div>
            <input type="hidden" name="cliente_id" :value="selecionado?.id || ''">
            <div x-show="aberto && resultados.length" @click.outside="aberto = false" x-cloak
                 class="absolute z-20 left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg max-h-72 overflow-y-auto">
                <template x-for="c in resultados" :key="c.id">
                    <button type="button" @click="escolher(c)" class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0 text-sm">
                        <p class="font-medium text-slate-700" x-text="c.nome"></p>
                        <p class="text-xs text-slate-500"><span x-text="c.telefone"></span></p>
                    </button>
                </template>
            </div>
        </div>
        <div class="flex gap-2">
            <input type="number" name="quantidade" value="1" min="1" max="50" class="w-24 border rounded-lg px-3 py-2 text-sm">
            <button class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm" :disabled="!selecionado">Creditar</button>
        </div>
    </form>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm p-5 mt-4">
    <h2 class="text-lg font-semibold mb-3">Top participantes</h2>
    @if ($porCliente->isEmpty())
        <p class="text-sm text-slate-400 text-center py-6">Nenhum bilhete ainda.</p>
    @else
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 border-b"><tr class="text-left"><th class="py-2">Cliente</th><th class="text-right">Bilhetes</th></tr></thead>
            <tbody>
                @foreach ($porCliente as $p)
                    <tr class="border-b last:border-b-0">
                        <td class="py-2">
                            <p class="font-medium text-slate-700">{{ $p->cliente->nome ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $p->cliente->telefone ?? '' }}</p>
                        </td>
                        <td class="text-right font-bold">{{ $p->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mt-4">
    <h2 class="text-lg font-semibold mb-3">Bilhetes ({{ $bilhetes->total() }})</h2>
    @if ($bilhetes->isEmpty())
        <p class="text-sm text-slate-400 text-center py-6">Nenhum bilhete ainda.</p>
    @else
        <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 border-b">
                <tr class="text-left"><th class="py-2">#</th><th>Cliente</th><th>Origem</th><th>Data</th></tr>
            </thead>
            <tbody>
                @foreach ($bilhetes as $b)
                    <tr class="border-b last:border-b-0 {{ $sorteio->vencedor_bilhete_id === $b->id ? 'bg-amber-50' : '' }}">
                        <td class="py-2 font-mono text-slate-600">{{ $b->numeroFormatado() }} {{ $sorteio->vencedor_bilhete_id === $b->id ? '🏆' : '' }}</td>
                        <td>{{ $b->cliente->nome ?? '—' }}</td>
                        <td class="text-xs"><span class="bg-slate-100 px-2 py-0.5 rounded-full">{{ \App\Models\SorteioBilhete::ORIGENS[$b->origem] ?? $b->origem }}</span></td>
                        <td class="text-xs text-slate-500">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $bilhetes->links() }}</div>
    @endif
</div>

<script>
function buscaCliente() {
    return {
        termo: '', aberto: false, resultados: [], selecionado: null,
        async buscar() {
            const q = this.termo.trim();
            if (q.length < 3) { this.resultados = []; this.aberto = false; return; }
            try {
                const r = await fetch(`{{ route('admin.caixa.buscar') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await r.json();
                this.resultados = data.clientes || [];
                this.aberto = this.resultados.length > 0;
            } catch (e) { this.resultados = []; this.aberto = false; }
        },
        escolher(c) { this.selecionado = c; this.aberto = false; this.termo = c.nome; },
        limpar() { this.selecionado = null; this.termo = ''; this.resultados = []; },
    }
}
</script>
@endsection
