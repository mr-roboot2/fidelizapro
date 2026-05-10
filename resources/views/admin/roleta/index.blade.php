@extends('layouts.admin')
@section('title', 'Roleta da sorte')
@section('content')
<div x-data="roletaAdmin()" class="space-y-6">

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
                                <td class="font-medium">{{ $p->label }}</td>
                                <td>{{ \App\Models\RoletaPremio::TIPOS[$p->tipo] ?? $p->tipo }}</td>
                                <td class="text-slate-600">
                                    @switch($p->tipo)
                                        @case('recompensa') {{ $p->recompensa->nome ?? '—' }} @break
                                        @case('pontos') {{ $p->pontos }} pts @break
                                        @default —
                                    @endswitch
                                </td>
                                <td>{{ $p->peso }}</td>
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

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="text-lg font-semibold mb-3">Creditar giro manualmente</h2>
        <form action="{{ route('admin.roleta.creditar', $roleta) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="text-xs text-slate-600">ID do cliente</label>
                <input type="number" name="cliente_id" min="1" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="ex: 42">
            </div>
            <div class="flex gap-2">
                <input type="number" name="giros" value="1" min="1" max="50" class="w-24 border rounded-lg px-3 py-2 text-sm">
                <button class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm">Creditar</button>
            </div>
        </form>
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

                <div>
                    <label class="text-xs text-slate-600">Peso (chance relativa) — quanto maior, mais provável</label>
                    <input type="number" name="peso" x-model="modal.dados.peso" min="0" max="1000" class="w-full border rounded-lg px-3 py-2 text-sm" required>
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
function roletaAdmin() {
    const roletaId = {{ $roleta->id }};
    const novoUrl = `{{ route('admin.roleta.premios.store', $roleta) }}`;
    const editarUrlBase = `{{ url('admin/roleta/'.$roleta->id.'/premios') }}`;

    return {
        modal: {
            aberto: false,
            editando: false,
            action: novoUrl,
            dados: { ordem: 0, label: '', cor: '#6366f1', tipo: 'pontos', recompensa_id: '', pontos: 10, peso: 10, ativo: true },
        },
        abrirNovoPremio() {
            this.modal.editando = false;
            this.modal.action = novoUrl;
            this.modal.dados = { ordem: 0, label: '', cor: '#6366f1', tipo: 'pontos', recompensa_id: '', pontos: 10, peso: 10, ativo: true };
            this.modal.aberto = true;
        },
        abrirEditarPremio(p) {
            this.modal.editando = true;
            this.modal.action = `${editarUrlBase}/${p.id}`;
            this.modal.dados = {
                ordem: p.ordem, label: p.label, cor: p.cor, tipo: p.tipo,
                recompensa_id: p.recompensa_id ?? '', pontos: p.pontos ?? 10,
                peso: p.peso, ativo: !!p.ativo,
            };
            this.modal.aberto = true;
        },
    }
}
</script>
@endsection
