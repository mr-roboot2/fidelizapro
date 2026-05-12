@extends('layouts.admin')
@section('title', $cliente->nome)
@section('content')
@if (session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-sm">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold">
                {{ strtoupper(substr($cliente->nome, 0, 1)) }}
            </div>
            <div>
                <h2 class="font-bold">{{ $cliente->nome }}</h2>
                <p class="text-sm text-slate-500">{{ $cliente->telefone }}</p>
            </div>
        </div>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">E-mail</dt><dd>{{ $cliente->email ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">CPF</dt><dd>{{ $cliente->cpf ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Aniversário</dt><dd>{{ $cliente->data_nascimento?->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Cadastro</dt><dd>{{ $cliente->created_at->format('d/m/Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Última compra</dt><dd>{{ $cliente->ultima_compra?->format('d/m/Y') ?? 'nunca' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Código indicação</dt><dd class="font-mono">{{ $cliente->codigo_indicacao }}</dd></div>
        </dl>

        <div class="mt-5 grid grid-cols-2 gap-2 text-center">
            <div class="bg-amber-50 rounded-lg p-3">
                <p class="text-xs text-amber-700">Pontos</p>
                <p class="text-xl font-bold text-amber-700">{{ number_format($cliente->pontos_atual, 0, ',', '.') }}</p>
            </div>
            <div class="bg-emerald-50 rounded-lg p-3">
                <p class="text-xs text-emerald-700">Cashback</p>
                <p class="text-xl font-bold text-emerald-700">R$ {{ number_format($cliente->cashback_atual, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-5 flex gap-2">
            <a href="{{ route('admin.caixa.index', ['cliente_id' => $cliente->id]) }}"
               class="flex-1 text-center text-sm bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
                <i class="ri-cash-line"></i> Lançar compra
            </a>
            <a href="{{ route('admin.clientes.edit', $cliente) }}"
               class="px-3 text-center text-sm bg-slate-200 py-2 rounded-lg">
                <i class="ri-edit-line"></i>
            </a>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-200">
            <h3 class="font-semibold text-sm mb-1 flex items-center gap-1.5">
                <i class="ri-equalizer-line text-slate-500"></i> Ajustar saldo manualmente
            </h3>
            <p class="text-xs text-slate-500 mb-3">Valor positivo credita, negativo debita. O motivo fica registrado no histórico.</p>

            <form method="POST" action="{{ route('admin.clientes.pontos', $cliente) }}" class="space-y-2 mb-4">
                @csrf
                <label class="block text-xs font-semibold text-amber-700 uppercase tracking-wide">Pontos</label>
                <div class="flex gap-2">
                    <input name="valor" type="number" step="1" placeholder="+50 ou -50" required
                           class="w-28 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <input name="motivo" type="text" placeholder="Motivo (ex: bônus, correção)" required maxlength="255"
                           class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full text-sm bg-amber-600 text-white py-2 rounded-lg hover:bg-amber-700"
                        onclick="return confirm('Aplicar ajuste de pontos?');">
                    Aplicar ajuste de pontos
                </button>
            </form>

            <form method="POST" action="{{ route('admin.clientes.cashback', $cliente) }}" class="space-y-2">
                @csrf
                <label class="block text-xs font-semibold text-emerald-700 uppercase tracking-wide">Cashback (R$)</label>
                <div class="flex gap-2">
                    <input name="valor" type="number" step="0.01" placeholder="+10,00 ou -10,00" required
                           class="w-28 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <input name="motivo" type="text" placeholder="Motivo (ex: bônus, correção)" required maxlength="255"
                           class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full text-sm bg-emerald-600 text-white py-2 rounded-lg hover:bg-emerald-700"
                        onclick="return confirm('Aplicar ajuste de cashback?');">
                    Aplicar ajuste de cashback
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Últimas compras</h3>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->compras as $c)
                    <li class="py-2 flex justify-between">
                        <div>
                            <p class="font-medium">{{ $c->descricao ?? 'Compra' }}</p>
                            <p class="text-xs text-slate-500">{{ $c->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-emerald-600">R$ {{ number_format($c->valor, 2, ',', '.') }}</p>
                            <p class="text-xs text-amber-600">+{{ number_format($c->pontos_gerados, 0, ',', '.') }} pts</p>
                        </div>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm">Nenhuma compra ainda.</p>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold mb-3">Resgates</h3>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->resgates as $r)
                    <li class="py-2 flex justify-between">
                        <div>
                            <p class="font-medium">{{ $r->recompensa->nome }}</p>
                            <p class="text-xs text-slate-500">{{ $r->codigo }} • {{ $r->created_at->format('d/m/Y') }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ ucfirst($r->status) }}</span>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm">Nenhum resgate ainda.</p>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5" data-historico="pontos">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <h3 class="font-semibold flex items-center gap-2">
                    <i class="ri-coin-line text-amber-600"></i> Movimentação de pontos
                    <span class="text-xs font-normal text-slate-500">({{ $cliente->transacoesPontos->count() }})</span>
                </h3>
                <div class="flex gap-1 text-xs">
                    <button type="button" onclick="filtrarHistorico(this, 'pontos', 'todos')" class="filtro-btn ativo px-3 py-1 rounded-full bg-slate-200 text-slate-700">Todos</button>
                    <button type="button" onclick="filtrarHistorico(this, 'pontos', 'credito')" class="filtro-btn px-3 py-1 rounded-full hover:bg-slate-100 text-slate-600">Créditos</button>
                    <button type="button" onclick="filtrarHistorico(this, 'pontos', 'debito')" class="filtro-btn px-3 py-1 rounded-full hover:bg-slate-100 text-slate-600">Débitos</button>
                </div>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->transacoesPontos as $t)
                    <li class="py-3 flex items-start gap-3" data-tipo="{{ $t->tipo }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $t->tipo === 'credito' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                            <i class="ri-{{ $t->tipo === 'credito' ? 'arrow-up' : 'arrow-down' }}-line"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-800">{{ $t->descricao }}</p>
                            <div class="flex flex-wrap gap-x-3 text-xs text-slate-500 mt-0.5">
                                <span>{{ $t->created_at->format('d/m/Y H:i') }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $t->origem }}</span>
                                <span>saldo após: <strong>{{ number_format($t->saldo_posterior, 0, ',', '.') }} pts</strong></span>
                            </div>
                        </div>
                        <p class="font-bold whitespace-nowrap {{ $t->tipo === 'credito' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $t->tipo === 'credito' ? '+' : '−' }}{{ number_format($t->pontos, 0, ',', '.') }}
                        </p>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm py-3">Sem movimentações.</p>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5" data-historico="cashback">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <h3 class="font-semibold flex items-center gap-2">
                    <i class="ri-money-dollar-circle-line text-emerald-600"></i> Movimentação de cashback
                    <span class="text-xs font-normal text-slate-500">({{ $cliente->movimentosCashback->count() }})</span>
                </h3>
                <div class="flex gap-1 text-xs">
                    <button type="button" onclick="filtrarHistorico(this, 'cashback', 'todos')" class="filtro-btn ativo px-3 py-1 rounded-full bg-slate-200 text-slate-700">Todos</button>
                    <button type="button" onclick="filtrarHistorico(this, 'cashback', 'credito')" class="filtro-btn px-3 py-1 rounded-full hover:bg-slate-100 text-slate-600">Créditos</button>
                    <button type="button" onclick="filtrarHistorico(this, 'cashback', 'debito')" class="filtro-btn px-3 py-1 rounded-full hover:bg-slate-100 text-slate-600">Débitos</button>
                </div>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($cliente->movimentosCashback as $m)
                    <li class="py-3 flex items-start gap-3" data-tipo="{{ $m->tipo }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $m->tipo === 'credito' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                            <i class="ri-{{ $m->tipo === 'credito' ? 'arrow-up' : 'arrow-down' }}-line"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-800">{{ $m->descricao }}</p>
                            <div class="flex flex-wrap gap-x-3 text-xs text-slate-500 mt-0.5">
                                <span>{{ $m->created_at->format('d/m/Y H:i') }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $m->origem }}</span>
                                <span>saldo após: <strong>R$ {{ number_format($m->saldo_posterior, 2, ',', '.') }}</strong></span>
                                @if (!$m->processado)
                                    <span class="text-amber-600"><i class="ri-time-line"></i> pendente até {{ $m->liberado_em?->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <p class="font-bold whitespace-nowrap {{ $m->tipo === 'credito' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $m->tipo === 'credito' ? '+' : '−' }}R$ {{ number_format($m->valor, 2, ',', '.') }}
                        </p>
                    </li>
                @empty
                    <p class="text-slate-400 text-sm py-3">Sem movimentações.</p>
                @endforelse
            </ul>
        </div>

        <script>
        function filtrarHistorico(btn, secao, tipo) {
            const card = document.querySelector(`[data-historico="${secao}"]`);
            card.querySelectorAll('.filtro-btn').forEach(b => {
                b.classList.remove('ativo', 'bg-slate-200', 'text-slate-700');
                b.classList.add('hover:bg-slate-100', 'text-slate-600');
            });
            btn.classList.add('ativo', 'bg-slate-200', 'text-slate-700');
            btn.classList.remove('hover:bg-slate-100', 'text-slate-600');
            card.querySelectorAll('li[data-tipo]').forEach(li => {
                li.style.display = (tipo === 'todos' || li.dataset.tipo === tipo) ? '' : 'none';
            });
        }
        </script>
    </div>
</div>
@endsection
