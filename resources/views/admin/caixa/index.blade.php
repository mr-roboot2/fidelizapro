@extends('layouts.admin')
@section('title', 'Caixa rápido')
@section('content')
<div x-data="caixa()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">

    <!-- COLUNA ESQUERDA: busca/cadastro de cliente -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4 flex items-center gap-2">
            <i class="ri-user-search-line text-indigo-600"></i> 1. Identifique o cliente
        </h2>

        <div x-show="!cliente" x-cloak>
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" x-model="busca" @input.debounce.300ms="buscar()"
                       placeholder="Telefone, CPF, nome ou QR Code..."
                       autofocus
                       class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg text-base">
            </div>

            <div class="mt-3 space-y-2 max-h-72 overflow-y-auto">
                <template x-for="c in resultados" :key="c.id">
                    <button @click="cliente = c" class="w-full text-left p-3 bg-slate-50 hover:bg-indigo-50 rounded-lg flex justify-between items-center">
                        <div>
                            <p class="font-medium" x-text="c.nome"></p>
                            <p class="text-xs text-slate-500" x-text="c.telefone + (c.cpf ? ' • ' + c.cpf : '')"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-amber-600" x-text="fmtNum(c.pontos) + ' pts'"></p>
                            <p class="text-xs text-emerald-600" x-text="fmtBRL(c.cashback)"></p>
                        </div>
                    </button>
                </template>
                <p x-show="busca.length >= 3 && resultados.length === 0 && !carregando" class="text-center text-sm text-slate-400 py-4">
                    Nenhum cliente encontrado.
                    <button @click="abrirCadastro = true" class="text-indigo-600 font-medium block mt-2">
                        <i class="ri-user-add-line"></i> Cadastrar novo cliente
                    </button>
                </p>
            </div>

            <button @click="abrirCadastro = true" x-show="!abrirCadastro"
                    class="mt-4 w-full py-2 border-2 border-dashed border-slate-300 rounded-lg text-sm text-slate-500 hover:bg-slate-50">
                <i class="ri-user-add-line"></i> Cadastrar cliente novo
            </button>

            <!-- Form de cadastro rápido -->
            <div x-show="abrirCadastro" x-cloak class="mt-4 p-4 bg-indigo-50 rounded-lg space-y-2">
                <h3 class="font-semibold text-sm mb-2">Cadastro rápido</h3>
                <input type="text" x-model="novo.nome" placeholder="Nome completo *" class="w-full px-3 py-2 border border-slate-300 rounded">
                <input type="text" x-model="novo.telefone" placeholder="Telefone *" class="w-full px-3 py-2 border border-slate-300 rounded">
                <input type="text" x-model="novo.cpf" placeholder="CPF (opcional)" class="w-full px-3 py-2 border border-slate-300 rounded">
                <input type="date" x-model="novo.data_nascimento" placeholder="Aniversário" class="w-full px-3 py-2 border border-slate-300 rounded">
                <div class="flex gap-2 pt-2">
                    <button @click="cadastrar()" class="flex-1 py-2 bg-indigo-600 text-white rounded">Cadastrar</button>
                    <button @click="abrirCadastro = false" class="px-4 py-2 bg-slate-200 rounded">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Cliente identificado -->
        <div x-show="cliente" x-cloak>
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-xl p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-white/80 text-sm">Cliente</p>
                        <p class="text-xl font-bold" x-text="cliente?.nome"></p>
                        <p class="text-white/80 text-sm" x-text="cliente?.telefone"></p>
                    </div>
                    <button @click="resetar()" class="text-white/80 hover:text-white">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="bg-white/15 rounded-lg p-3">
                        <p class="text-xs text-white/80">Saldo de pontos</p>
                        <p class="text-2xl font-bold" x-text="fmtNum(cliente?.pontos)"></p>
                    </div>
                    <div class="bg-white/15 rounded-lg p-3">
                        <p class="text-xs text-white/80">Cashback</p>
                        <p class="text-2xl font-bold" x-text="fmtBRL(cliente?.cashback)"></p>
                        <p x-show="cliente?.cashback_pendente > 0" class="text-[10px] text-white/70 mt-0.5">
                            <span x-text="fmtBRL(cliente?.cashback_pendente)"></span> pendente
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COLUNA DIREITA: lançamento -->
    <div class="bg-white rounded-xl shadow-sm p-6" :class="!cliente && 'opacity-50 pointer-events-none'">
        <h2 class="font-semibold text-lg mb-4 flex items-center gap-2">
            <i class="ri-money-dollar-circle-line text-emerald-600"></i> 2. Lance a compra
        </h2>

        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium">Valor da compra (R$) *</label>
                <input type="number" x-model.number="valor" step="0.01" min="0.01" placeholder="0,00"
                       class="mt-1 w-full px-3 py-3 border border-slate-300 rounded-lg text-2xl font-bold text-right">
            </div>

            <div x-show="cliente?.cashback > 0" x-cloak>
                <label class="text-sm font-medium">Usar cashback (R$)</label>
                <div class="flex gap-2 mt-1">
                    <input type="number" x-model.number="usarCashback" step="0.01" min="0" :max="Math.min(cliente?.cashback || 0, valor || 0)"
                           class="flex-1 px-3 py-2 border border-slate-300 rounded-lg">
                    <button @click="usarCashback = Math.min(cliente?.cashback || 0, valor || 0)"
                            class="px-3 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-sm">
                        Usar tudo
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-1">Disponível: <span x-text="fmtBRL(cliente?.cashback)"></span></p>
            </div>

            <div>
                <label class="text-sm font-medium">Descrição (opcional)</label>
                <input type="text" x-model="descricao" placeholder="Ex: Almoço executivo"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>

            <div class="bg-slate-50 rounded-lg p-3 text-sm space-y-1">
                <div class="flex justify-between"><span>Valor da compra</span><span x-text="fmtBRL(valor)"></span></div>
                <div class="flex justify-between text-rose-600" x-show="usarCashback > 0">
                    <span>Desconto cashback</span><span x-text="'− ' + fmtBRL(usarCashback)"></span>
                </div>
                <div class="flex justify-between font-bold pt-2 border-t border-slate-200">
                    <span>Total a pagar</span><span x-text="fmtBRL((valor || 0) - (usarCashback || 0))"></span>
                </div>
            </div>

            <button @click="lancar()" :disabled="!valor || carregando"
                    class="w-full py-3 bg-emerald-600 text-white rounded-lg text-lg font-bold hover:bg-emerald-700 disabled:bg-slate-300">
                <span x-show="!carregando"><i class="ri-check-line"></i> Confirmar compra</span>
                <span x-show="carregando"><i class="ri-loader-4-line animate-spin"></i> Processando...</span>
            </button>
        </div>

        <!-- Confirmação -->
        <div x-show="ultimaCompra" x-cloak class="mt-4 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
            <p class="font-semibold text-emerald-700"><i class="ri-check-double-line"></i> Compra registrada!</p>
            <div class="text-sm mt-2 grid grid-cols-2 gap-2">
                <div>
                    <p class="text-slate-500 text-xs">Pontos gerados</p>
                    <p class="font-bold text-amber-600" x-text="'+' + fmtNum(ultimaCompra?.pontos_gerados)"></p>
                </div>
                <div>
                    <p class="text-slate-500 text-xs">Cashback gerado</p>
                    <p class="font-bold text-emerald-600" x-text="fmtBRL(ultimaCompra?.cashback_gerado)"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function caixa() {
    return {
        busca: '', resultados: [], carregando: false,
        cliente: null, abrirCadastro: false,
        novo: { nome: '', telefone: '', cpf: '', data_nascimento: '' },
        valor: null, usarCashback: 0, descricao: '',
        ultimaCompra: null,
        csrf: document.querySelector('meta[name=csrf-token]').content,

        init() {},

        async buscar() {
            if (this.busca.length < 3) { this.resultados = []; return; }
            this.carregando = true;
            const r = await fetch(`{{ route('admin.caixa.buscar') }}?q=${encodeURIComponent(this.busca)}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf }
            });
            const d = await r.json();
            this.resultados = d.clientes || [];
            this.carregando = false;
        },

        async cadastrar() {
            this.carregando = true;
            const r = await fetch(`{{ route('admin.caixa.criar') }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(this.novo),
            });
            const d = await r.json();
            this.carregando = false;
            if (!r.ok) { alert(d.message || JSON.stringify(d.errors)); return; }
            this.cliente = d.cliente;
            this.abrirCadastro = false;
        },

        async lancar() {
            if (!this.cliente || !this.valor) return;
            this.carregando = true;
            const r = await fetch(`{{ route('admin.caixa.lancar') }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({
                    cliente_id: this.cliente.id,
                    valor: this.valor,
                    usar_cashback: this.usarCashback || 0,
                    descricao: this.descricao,
                }),
            });
            const d = await r.json();
            this.carregando = false;
            if (!r.ok) { alert(d.message || 'Erro'); return; }
            this.ultimaCompra = d.compra;
            // atualiza saldo do cliente na tela
            this.cliente.pontos = d.cliente.pontos;
            this.cliente.cashback = d.cliente.cashback;
            this.cliente.cashback_pendente = d.cliente.cashback_pendente;
            this.valor = null; this.usarCashback = 0; this.descricao = '';
            setTimeout(() => this.ultimaCompra = null, 8000);
        },

        resetar() {
            this.cliente = null; this.busca = ''; this.resultados = [];
            this.valor = null; this.usarCashback = 0; this.descricao = '';
            this.ultimaCompra = null;
        },

        fmtBRL(v) { return 'R$ ' + Number(v || 0).toFixed(2).replace('.', ','); },
        fmtNum(v) { return Number(v || 0).toLocaleString('pt-BR', {maximumFractionDigits: 0}); },
    };
}
</script>
@endsection
