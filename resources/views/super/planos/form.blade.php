@extends('layouts.super')
@section('title', $plano->exists ? 'Editar plano' : 'Novo plano')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <form method="POST" action="{{ $plano->exists ? route('super.planos.update', $plano) : route('super.planos.store') }}">
        @csrf
        @if ($plano->exists) @method('PUT') @endif

        <h3 class="font-semibold mb-4">Dados do plano</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $plano->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div x-data="{
                    centavos: {{ (int) round(((float) old('preco_mensal', $plano->preco_mensal ?? 0)) * 100) }},
                    get formatado() {
                        return (this.centavos / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    },
                    get numerico() {
                        return (this.centavos / 100).toFixed(2);
                    },
                    digitar(e) {
                        const apenas = e.target.value.replace(/\D/g, '');
                        this.centavos = parseInt(apenas || '0', 10);
                    }
                 }">
                <label class="text-sm font-medium">Preço mensal (R$) *</label>
                {{-- Input visual mostra "R$ 97,00" formatado conforme digita.
                     O valor real (97.00) vai no hidden abaixo pra não quebrar
                     o backend que espera decimal. --}}
                <input type="text" inputmode="numeric" required
                       :value="formatado" @input="digitar($event)"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono">
                <input type="hidden" name="preco_mensal" :value="numerico">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Descrição</label>
                <textarea name="descricao" rows="2" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">{{ old('descricao', $plano->descricao) }}</textarea>
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-4">Limites (vazio = ilimitado)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium">Clientes</label>
                <input type="number" name="limite_clientes" min="1" value="{{ old('limite_clientes', $plano->limite_clientes) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-[11px] text-slate-500 mt-1">Quantos clientes finais a empresa pode ter cadastrados no programa de fidelidade.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Compras/mês</label>
                <input type="number" name="limite_compras_mes" min="1" value="{{ old('limite_compras_mes', $plano->limite_compras_mes) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-[11px] text-slate-500 mt-1">Quantas compras podem ser lançadas no caixa por mês. Contador zera no dia 1.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Recompensas ativas</label>
                <input type="number" name="limite_recompensas" min="1" value="{{ old('limite_recompensas', $plano->limite_recompensas) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-[11px] text-slate-500 mt-1">Quantas recompensas podem estar publicadas no catálogo ao mesmo tempo.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Parceiros</label>
                <input type="number" name="limite_parceiros" min="1" value="{{ old('limite_parceiros', $plano->limite_parceiros) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-[11px] text-slate-500 mt-1">Quantos parceiros (lojas amigas) podem estar ativos pra cross-promoção. Requer módulo Parceiros.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Atendentes</label>
                <input type="number" name="limite_users" min="1" value="{{ old('limite_users', $plano->limite_users) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-[11px] text-slate-500 mt-1">Quantos gerentes + atendentes a empresa pode cadastrar. O dono (admin) não entra na conta.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Campanhas/mês</label>
                <input type="number" name="limite_campanhas_mes" min="1" value="{{ old('limite_campanhas_mes', $plano->limite_campanhas_mes) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                <p class="text-[11px] text-slate-500 mt-1">Quantas campanhas WhatsApp pode disparar por mês. Contador zera no dia 1.</p>
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-1">Módulos habilitados pra esse plano</h3>
        <p class="text-xs text-slate-500 mb-3">Marque os recursos que a empresa terá acesso. Os não marcados ficam ocultos no menu admin dela.</p>
        @php $modulosAtuais = old('modulos', $plano->modulos ?? []); @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach (\App\Models\Plano::MODULOS_DISPONIVEIS as $chave => $rotulo)
                <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-slate-100 rounded-lg cursor-pointer transition border border-transparent has-[:checked]:border-rose-400 has-[:checked]:bg-rose-50">
                    <input type="checkbox" name="modulos[]" value="{{ $chave }}" {{ in_array($chave, $modulosAtuais, true) ? 'checked' : '' }} class="text-rose-600 rounded">
                    <span class="text-sm">{{ $rotulo }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-4 flex items-center gap-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $plano->ativo ?? true) ? 'checked':'' }}>
                <span class="text-sm font-medium">Plano ativo</span>
            </label>
            <div>
                <label class="text-sm font-medium">Ordem</label>
                <input type="number" name="ordem" value="{{ old('ordem', $plano->ordem ?? 0) }}"
                       class="ml-2 w-20 px-2 py-1 border border-slate-300 rounded text-sm">
            </div>
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-rose-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('super.planos.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
