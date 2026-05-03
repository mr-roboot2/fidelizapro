@extends("layouts.super")
@section('title', $automacao->exists ? 'Editar automação' : 'Configurar automação')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <form method="POST" action="{{ $automacao->exists ? route('super.automacoes.update', $automacao) : route('super.automacoes.store') }}">
        @csrf
        @if ($automacao->exists) @method('PUT') @endif
        @unless ($automacao->exists)
            <input type="hidden" name="tipo" value="{{ $automacao->tipo }}">
        @endunless

        <div class="bg-slate-50 rounded-lg p-3 mb-4 text-sm">
            <p class="font-semibold">{{ \App\Models\Automacao::TIPOS[$automacao->tipo] ?? '' }}</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium">Nome interno *</label>
                <input type="text" name="nome" required value="{{ old('nome', $automacao->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>

            <div>
                <label class="text-sm font-medium">Mensagem WhatsApp *</label>
                <textarea name="mensagem" required rows="8" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">{{ old('mensagem', $automacao->mensagem) }}</textarea>
                <div class="text-xs text-slate-500 mt-1">
                    Variáveis disponíveis:
                    <code>{nome}</code>,
                    <code>{primeiro_nome}</code>,
                    <code>{pontos}</code>,
                    <code>{cashback}</code>,
                    <code>{empresa}</code>
                    @if ($automacao->tipo === 'pos_compra')
                        , <code>{valor_compra}</code>, <code>{pontos_ganhos}</code>
                    @endif
                    @if ($automacao->tipo === 'agradecimento_resgate')
                        , <code>{recompensa}</code>, <code>{codigo_resgate}</code>
                    @endif
                </div>
            </div>

            @if (in_array($automacao->tipo, ['pontos_vencendo']))
                <div>
                    <label class="text-sm font-medium">Avisar quantos dias antes? *</label>
                    <input type="number" name="dias_offset" required min="1" max="60"
                           value="{{ old('dias_offset', $automacao->dias_offset ?: 7) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            @endif

            <label class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $automacao->ativo) ? 'checked' : '' }} class="rounded">
                <span class="text-sm font-medium">Automação ativa</span>
            </label>
        </div>

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar</button>
            <a href="{{ route('super.automacoes.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
            @if ($automacao->exists)
                <form action="{{ route('super.automacoes.destroy', $automacao) }}" method="POST" class="ml-auto" onsubmit="return confirm('Remover automação?')">
                    @csrf @method('DELETE')
                    <button class="px-4 py-2 bg-rose-100 text-rose-700 rounded-lg text-sm">Excluir</button>
                </form>
            @endif
        </div>
    </form>
</div>
@endsection
