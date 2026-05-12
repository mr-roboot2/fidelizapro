@extends('layouts.admin')
@section('title', 'Caixa rápido')
@section('content')
<div x-data="caixa(@js($clientePre ? [
        'id' => $clientePre->id,
        'nome' => $clientePre->nome,
        'telefone' => $clientePre->telefone,
        'pontos' => (float) $clientePre->pontos_atual,
        'cashback' => (float) $clientePre->cashback_atual,
        'cashback_pendente' => (float) $clientePre->cashback_pendente,
    ] : null)" x-init="init()" class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">

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

                <input type="text" x-model="novo.nome" placeholder="Nome completo *"
                       :class="erros.nome ? 'border-rose-400' : 'border-slate-300'"
                       class="w-full px-3 py-2 border rounded">
                <p x-show="erros.nome" x-text="erros.nome" class="text-xs text-rose-600 -mt-1"></p>

                <input type="text" x-model="novo.telefone" placeholder="Telefone *" maxlength="15" inputmode="numeric"
                       @input="novo.telefone = mascararTelefone($event.target.value)"
                       :class="erros.telefone ? 'border-rose-400' : 'border-slate-300'"
                       class="w-full px-3 py-2 border rounded">
                <p x-show="erros.telefone" x-text="erros.telefone" class="text-xs text-rose-600 -mt-1"></p>

                <input type="text" x-model="novo.cpf" placeholder="CPF *" maxlength="14" inputmode="numeric"
                       @input="novo.cpf = mascararCpf($event.target.value)"
                       :class="erros.cpf ? 'border-rose-400' : 'border-slate-300'"
                       class="w-full px-3 py-2 border rounded">
                <p x-show="erros.cpf" x-text="erros.cpf" class="text-xs text-rose-600 -mt-1"></p>

                <input type="date" x-model="novo.data_nascimento" placeholder="Aniversário" class="w-full px-3 py-2 border border-slate-300 rounded">

                <div class="flex gap-2 pt-2">
                    <button @click="cadastrar()" :disabled="salvando"
                            class="flex-1 py-2 bg-indigo-600 text-white rounded disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <i x-show="salvando" class="ri-loader-4-line animate-spin"></i>
                        <span x-text="salvando ? 'Cadastrando...' : 'Cadastrar'"></span>
                    </button>
                    <button @click="abrirCadastro = false" :disabled="salvando" class="px-4 py-2 bg-slate-200 rounded disabled:opacity-60">Cancelar</button>
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

            <!-- Aviso de senha temporária (logo após cadastro pelo caixa) -->
            <div x-show="senhaTemp" x-cloak class="mt-3 bg-amber-50 border-2 border-amber-300 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i class="ri-key-2-fill text-amber-600 text-2xl"></i>
                    <div class="flex-1">
                        <p class="font-bold text-amber-900 text-sm">Senha temporária do cliente:</p>
                        <p class="font-mono text-3xl font-bold text-amber-900 my-1 tracking-widest" x-text="senhaTemp"></p>
                        <p class="text-xs text-amber-800 leading-snug">
                            Avise o cliente que pra entrar no app é só usar os <strong>últimos 6 dígitos do celular</strong>.
                            Ele será obrigado a trocar a senha no primeiro acesso.
                        </p>
                    </div>
                    <button @click="senhaTemp = null" class="text-amber-700 hover:text-amber-900">
                        <i class="ri-close-line"></i>
                    </button>
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
            <div class="flex gap-2 mt-3">
                <a :href="`{{ url('admin/caixa/cupom') }}/${ultimaCompra?.id}?auto=1`" target="_blank"
                   class="flex-1 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg text-center">
                    <i class="ri-printer-line"></i> Imprimir cupom (2 vias)
                </a>
                <a :href="`{{ url('admin/caixa/cupom') }}/${ultimaCompra?.id}`" target="_blank"
                   class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg" title="Visualizar antes de imprimir">
                    <i class="ri-eye-line"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function caixa(clientePre) {
    return {
        busca: '', resultados: [], carregando: false,
        cliente: clientePre || null, abrirCadastro: false, salvando: false, senhaTemp: null,
        novo: { nome: '', telefone: '', cpf: '', data_nascimento: '' },
        erros: {},
        valor: null, usarCashback: 0, descricao: '',
        ultimaCompra: null,
        csrf: document.querySelector('meta[name=csrf-token]').content,

        mascararTelefone(v) {
            v = String(v || '').replace(/\D/g, '').slice(0, 11);
            if (!v) return '';
            if (v.length <= 2) return '(' + v;
            if (v.length <= 6) return '(' + v.slice(0,2) + ') ' + v.slice(2);
            if (v.length <= 10) return '(' + v.slice(0,2) + ') ' + v.slice(2,6) + '-' + v.slice(6);
            return '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
        },
        mascararCpf(v) {
            v = String(v || '').replace(/\D/g, '').slice(0, 11);
            if (v.length > 9) return v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9);
            if (v.length > 6) return v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6);
            if (v.length > 3) return v.slice(0,3)+'.'+v.slice(3);
            return v;
        },
        validarTelefone(t) {
            const d = String(t || '').replace(/\D/g, '');
            if (d.length !== 10 && d.length !== 11) return false;
            const ddd = parseInt(d.slice(0,2));
            if (ddd < 11 || ddd > 99) return false;
            if (d.length === 11 && d[2] !== '9') return false;
            return true;
        },
        validarCpf(cpf) {
            cpf = String(cpf || '').replace(/\D/g, '');
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
            let soma = 0;
            for (let i = 0; i < 9; i++) soma += parseInt(cpf[i]) * (10 - i);
            let d1 = (soma * 10) % 11; if (d1 === 10) d1 = 0;
            if (d1 !== parseInt(cpf[9])) return false;
            soma = 0;
            for (let i = 0; i < 10; i++) soma += parseInt(cpf[i]) * (11 - i);
            let d2 = (soma * 10) % 11; if (d2 === 10) d2 = 0;
            return d2 === parseInt(cpf[10]);
        },

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
            this.erros = {};
            if (!this.novo.nome || this.novo.nome.trim().length < 2) this.erros.nome = 'Informe o nome completo';
            if (!this.validarTelefone(this.novo.telefone)) this.erros.telefone = 'Telefone inválido (DDD + número)';
            if (!this.validarCpf(this.novo.cpf)) this.erros.cpf = 'CPF inválido';
            if (Object.keys(this.erros).length) return;

            this.salvando = true;
            try {
                const r = await fetch(`{{ route('admin.caixa.criar') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify(this.novo),
                });
                const d = await r.json();
                if (!r.ok) {
                    if (d.errors) {
                        // Mapeia erros do Laravel (primeira msg de cada campo)
                        Object.keys(d.errors).forEach(k => this.erros[k] = d.errors[k][0]);
                    } else {
                        alert(d.message || 'Erro ao cadastrar');
                    }
                    return;
                }
                this.cliente = d.cliente;
                this.abrirCadastro = false;
                this.novo = { nome: '', telefone: '', cpf: '', data_nascimento: '' };
                if (d.senha_temporaria) this.senhaTemp = d.senha_temporaria;
            } finally {
                this.salvando = false;
            }
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
