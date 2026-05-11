@extends('layouts.admin')
@section('title', 'Roleta da sorte')
@section('content')
<div x-data="roletaAdmin()" class="space-y-6">

    <div class="flex justify-end">
        <a href="{{ route('admin.roleta.metricas') }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-900 text-white rounded-lg text-sm hover:bg-slate-800">
            <i class="ri-bar-chart-2-line"></i> Ver métricas
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5" x-data="buscaCliente()">
        <h2 class="text-lg font-semibold mb-3">Creditar giro manualmente</h2>
        <form action="{{ route('admin.roleta.creditar', $roleta) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end" @submit="if (!selecionado) { $event.preventDefault(); alert('Selecione um cliente da lista.'); }">
            @csrf
            <div class="md:col-span-2 relative">
                <label class="text-xs text-slate-600">Cliente (nome, telefone, CPF ou QR)</label>
                <input x-show="!selecionado" type="text" x-model="termo" @input.debounce.300ms="buscar()" @focus="if (resultados.length) aberto = true"
                       placeholder="Digite ao menos 3 caracteres…"
                       class="w-full border rounded-lg px-3 py-2 text-sm" autocomplete="off">
                <div x-show="selecionado" x-cloak
                     class="w-full border rounded-lg px-3 py-2 text-sm bg-emerald-50 border-emerald-300 flex items-center gap-2">
                    <i class="ri-check-line text-emerald-600"></i>
                    <span class="flex-1 font-medium text-slate-700 truncate" x-text="selecionado?.nome"></span>
                    <button type="button" @click="limpar()" class="text-xs text-rose-600 hover:underline shrink-0">trocar</button>
                </div>
                <input type="hidden" name="cliente_id" :value="selecionado?.id || ''">
                <div x-show="aberto && resultados.length" @click.outside="aberto = false" x-cloak
                     class="absolute z-20 left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg max-h-72 overflow-y-auto">
                    <template x-for="c in resultados" :key="c.id">
                        <button type="button" @click="escolher(c)"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0 text-sm">
                            <p class="font-medium text-slate-700" x-text="c.nome"></p>
                            <p class="text-xs text-slate-500">
                                <span x-text="c.telefone"></span>
                                <span x-show="c.cpf"> · CPF <span x-text="c.cpf"></span></span>
                                · <span x-text="Math.round(c.pontos)"></span> pts
                            </p>
                        </button>
                    </template>
                </div>
            </div>
            <div class="flex gap-2">
                <input type="number" name="giros" value="1" min="1" max="50" class="w-24 border rounded-lg px-3 py-2 text-sm">
                <button class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm" :disabled="!selecionado">Creditar</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <form method="POST" action="{{ route('admin.roleta.update', $roleta) }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <i class="ri-bubble-chart-line text-indigo-600"></i> Configuração da roleta
                    </h2>
                    <p class="text-sm text-slate-500">Cliente nunca perde — quem não ganhar prêmio recebe a consolação.</p>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="ativa" value="1" {{ $roleta->ativa ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-checked:bg-emerald-500 rounded-full relative transition">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                    </div>
                    <span class="text-sm font-medium" x-text="document.querySelector('[name=ativa]').checked ? 'Ativa' : 'Inativa'">
                        {{ $roleta->ativa ? 'Ativa' : 'Inativa' }}
                    </span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-slate-600">Nome</label>
                    <input name="nome" value="{{ old('nome', $roleta->nome) }}" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="text-xs text-slate-600">Limite de giros por dia (por cliente)</label>
                    <input type="number" name="limite_giros_dia" value="{{ old('limite_giros_dia', $roleta->limite_giros_dia) }}" min="1" max="50" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Pontos da consolação</label>
                    <input type="number" name="pontos_consolacao" value="{{ old('pontos_consolacao', $roleta->pontos_consolacao) }}" min="0" max="255" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Validade do prêmio (dias)</label>
                    <input type="number" name="validade_dias" value="{{ old('validade_dias', $roleta->validade_dias) }}" min="1" max="365" placeholder="vazio = sem validade" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Tempo mínimo da animação (ms)</label>
                    <input type="number" name="tempo_min_ms" value="{{ old('tempo_min_ms', $roleta->tempo_min_ms) }}" min="1500" max="15000" step="100" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-600">Tempo máximo da animação (ms)</label>
                    <input type="number" name="tempo_max_ms" value="{{ old('tempo_max_ms', $roleta->tempo_max_ms) }}" min="1500" max="15000" step="100" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <label class="text-xs text-slate-600">Mensagem de consolação (use {pontos} pra inserir os pontos ganhos)</label>
                    <input name="mensagem_consolacao" value="{{ old('mensagem_consolacao', $roleta->mensagem_consolacao) }}" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                </div>
            </div>

            <div class="flex items-center justify-between border-t pt-4">
                <div class="text-sm text-slate-500 flex gap-4">
                    <span><i class="ri-history-line"></i> Total de giros: <strong>{{ $totalGiros }}</strong></span>
                    <span><i class="ri-calendar-line"></i> Hoje: <strong>{{ $girosHoje }}</strong></span>
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Salvar configuração</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="text-lg font-semibold mb-1">Gatilhos automáticos</h2>
        <p class="text-xs text-slate-500 mb-4">Quando o sistema deve creditar giros automaticamente. Roda 1x por dia às 6h.</p>
        <div class="space-y-2">
            @foreach (\App\Models\RoletaGatilho::TIPOS as $tipo => $info)
                @php $g = $gatilhosPorTipo[$tipo] ?? null; @endphp
                <form action="{{ route('admin.roleta.gatilhos.salvar', $roleta) }}" method="POST"
                      class="flex flex-wrap items-center gap-3 p-3 border rounded-lg {{ $g && $g->ativo ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50' }}">
                    @csrf
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    <div class="flex-1 min-w-[200px]">
                        <p class="text-sm font-medium text-slate-700">{{ $info['rotulo'] }}</p>
                    </div>
                    @if ($info['campo'])
                        <div>
                            <input type="number" name="valor" value="{{ $g->valor ?? '' }}" min="1" placeholder="—"
                                   class="w-24 border rounded-lg px-2 py-1 text-sm">
                            <span class="text-xs text-slate-500 ml-1">{{ $info['sufixo'] }}</span>
                        </div>
                    @else
                        <input type="hidden" name="valor" value="">
                    @endif
                    <div>
                        <label class="text-xs text-slate-500">Giros</label>
                        <input type="number" name="giros" value="{{ $g->giros ?? 1 }}" min="1" max="50"
                               class="w-16 border rounded-lg px-2 py-1 text-sm">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="ativo" value="1" {{ $g && $g->ativo ? 'checked' : '' }}>
                        Ativo
                    </label>
                    <button class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs">Salvar</button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Prêmios da roleta</h2>
            <button @click="abrirNovoPremio()" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm">
                <i class="ri-add-line"></i> Adicionar prêmio
            </button>
        </div>

        @if ($roleta->premios->isEmpty())
            <p class="text-sm text-slate-400 text-center py-8">Nenhum prêmio configurado. Adicione pelo menos 4 pra a roleta funcionar.</p>
        @else
            @php
                $totalPeso = $roleta->premios->where('ativo', true)->sum('peso') ?: 1;
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 border-b">
                        <tr class="text-left">
                            <th class="py-2">Ordem</th>
                            <th>Cor</th>
                            <th>Label</th>
                            <th>Tipo</th>
                            <th>Conteúdo</th>
                            <th>Peso</th>
                            <th>Limite/dia</th>
                            <th>Probabilidade</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roleta->premios as $p)
                            <tr class="border-b">
                                <td class="py-2">{{ $p->ordem }}</td>
                                <td><span class="inline-block w-5 h-5 rounded" style="background:{{ $p->cor }}"></span></td>
                                <td>
                                    <p class="font-medium">{{ $p->label }}</p>
                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                        @if ($p->tier_minimo_pontos)
                                            <span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full">
                                                <i class="ri-vip-crown-line"></i> VIP {{ number_format($p->tier_minimo_pontos, 0, ',', '.') }}+ pts
                                            </span>
                                        @endif
                                        @if ($p->valido_de || $p->valido_ate)
                                            <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full">
                                                <i class="ri-calendar-event-line"></i>
                                                {{ $p->valido_de ? $p->valido_de->format('d/m') : '—' }}
                                                a
                                                {{ $p->valido_ate ? $p->valido_ate->format('d/m') : '—' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ \App\Models\RoletaPremio::TIPOS[$p->tipo] ?? $p->tipo }}</td>
                                <td class="text-slate-600">
                                    @switch($p->tipo)
                                        @case('recompensa') {{ $p->recompensa->nome ?? '—' }} @break
                                        @case('pontos') {{ $p->pontos }} pts @break
                                        @default —
                                    @endswitch
                                </td>
                                <td>{{ $p->peso }}</td>
                                <td>{{ $p->quantidade_max_dia ?? '∞' }}</td>
                                <td>{{ $p->ativo ? round($p->peso / $totalPeso * 100, 1).'%' : '—' }}</td>
                                <td>
                                    @if ($p->ativo)
                                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Ativo</span>
                                    @else
                                        <span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">Inativo</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button @click='abrirEditarPremio(@json($p))' class="text-indigo-600 text-xs hover:underline">Editar</button>
                                    <form action="{{ route('admin.roleta.premios.destroy', [$roleta, $p]) }}" method="POST" class="inline" onsubmit="return confirm('Remover prêmio?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 text-xs hover:underline ml-2">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-400 mt-3">A probabilidade de cada prêmio é proporcional ao peso. Soma dos pesos ativos: {{ $totalPeso }}.</p>
        @endif
    </div>

    <div x-show="modal.aberto" x-cloak class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4" @click.self="modal.aberto = false">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-5">
            <h3 class="text-lg font-semibold mb-3" x-text="modal.editando ? 'Editar prêmio' : 'Novo prêmio'"></h3>
            <form :method="modal.editando ? 'POST' : 'POST'" :action="modal.action" method="POST" class="space-y-3">
                @csrf
                <template x-if="modal.editando"><input type="hidden" name="_method" value="PUT"></template>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-600">Ordem</label>
                        <input type="number" name="ordem" x-model="modal.dados.ordem" min="0" max="255" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Cor</label>
                        <input type="color" name="cor" x-model="modal.dados.cor" class="w-full h-10 border rounded-lg" required>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-600">Label visível na roleta</label>
                    <input name="label" x-model="modal.dados.label" maxlength="60" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="text-xs text-slate-600">Tipo</label>
                    <select name="tipo" x-model="modal.dados.tipo" class="w-full border rounded-lg px-3 py-2 text-sm">
                        @foreach (\App\Models\RoletaPremio::TIPOS as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="modal.dados.tipo === 'recompensa'">
                    <label class="text-xs text-slate-600">Recompensa do catálogo</label>
                    <select name="recompensa_id" x-model="modal.dados.recompensa_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">— escolher —</option>
                        @foreach ($recompensas as $r)
                            <option value="{{ $r->id }}">{{ $r->nome }} ({{ $r->custo_pontos }} pts)</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="modal.dados.tipo === 'pontos'">
                    <label class="text-xs text-slate-600">Quantidade de pontos</label>
                    <input type="number" name="pontos" x-model="modal.dados.pontos" min="1" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-600">Peso (chance relativa)</label>
                        <input type="number" name="peso" x-model="modal.dados.peso" min="0" max="1000" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Limite por dia</label>
                        <input type="number" name="quantidade_max_dia" x-model="modal.dados.quantidade_max_dia" min="1" max="1000" placeholder="∞ sem limite" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="border-t pt-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Modo quente <span class="text-[10px] text-slate-400 font-normal lowercase">(prêmio só pra cliente VIP)</span></p>
                    <label class="text-xs text-slate-600">Pontos mínimos do cliente</label>
                    <input type="number" name="tier_minimo_pontos" x-model="modal.dados.tier_minimo_pontos" min="1" placeholder="vazio = todos podem ganhar" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="border-t pt-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Modo campanha <span class="text-[10px] text-slate-400 font-normal lowercase">(janela de datas)</span></p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-600">Válido de</label>
                            <input type="date" name="valido_de" x-model="modal.dados.valido_de" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-600">Válido até</label>
                            <input type="date" name="valido_ate" x-model="modal.dados.valido_ate" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="ativo" value="1" x-model="modal.dados.ativo">
                    Prêmio ativo
                </label>

                <div class="flex justify-end gap-2 border-t pt-3">
                    <button type="button" @click="modal.aberto = false" class="px-4 py-2 bg-slate-100 rounded-lg text-sm">Cancelar</button>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function buscaCliente() {
    return {
        termo: '',
        aberto: false,
        resultados: [],
        selecionado: null,
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

function roletaAdmin() {
    const roletaId = {{ $roleta->id }};
    const novoUrl = `{{ route('admin.roleta.premios.store', $roleta) }}`;
    const editarUrlBase = `{{ url('admin/roleta/'.$roleta->id.'/premios') }}`;

    return {
        modal: {
            aberto: false,
            editando: false,
            action: novoUrl,
            dados: { ordem: 0, label: '', cor: '#6366f1', tipo: 'pontos', recompensa_id: '', pontos: 10, peso: 10, quantidade_max_dia: '', tier_minimo_pontos: '', valido_de: '', valido_ate: '', ativo: true },
        },
        abrirNovoPremio() {
            this.modal.editando = false;
            this.modal.action = novoUrl;
            this.modal.dados = { ordem: 0, label: '', cor: '#6366f1', tipo: 'pontos', recompensa_id: '', pontos: 10, peso: 10, quantidade_max_dia: '', tier_minimo_pontos: '', valido_de: '', valido_ate: '', ativo: true };
            this.modal.aberto = true;
        },
        abrirEditarPremio(p) {
            this.modal.editando = true;
            this.modal.action = `${editarUrlBase}/${p.id}`;
            this.modal.dados = {
                ordem: p.ordem, label: p.label, cor: p.cor, tipo: p.tipo,
                recompensa_id: p.recompensa_id ?? '', pontos: p.pontos ?? 10,
                peso: p.peso, quantidade_max_dia: p.quantidade_max_dia ?? '',
                tier_minimo_pontos: p.tier_minimo_pontos ?? '',
                valido_de: p.valido_de ? p.valido_de.substring(0, 10) : '',
                valido_ate: p.valido_ate ? p.valido_ate.substring(0, 10) : '',
                ativo: !!p.ativo,
            };
            this.modal.aberto = true;
        },
    }
}
</script>
@endsection
