@extends("layouts.super")
@section('title', $automacao->exists ? 'Editar automação' : 'Configurar automação')
@section('content')
@php
    $tipoValido      = $automacao->tipo && array_key_exists($automacao->tipo, \App\Models\Automacao::TIPOS);
    $tipoLabel       = $tipoValido ? \App\Models\Automacao::TIPOS[$automacao->tipo] : '(tipo não reconhecido)';
    $nomeDefault     = $automacao->nome ?: $tipoLabel;
    $mensagemDefault = $automacao->mensagem ?: (\App\Models\Automacao::TEMPLATES_PADRAO[$automacao->tipo ?? ''] ?? '');
@endphp

@if ($automacao->exists && !$tipoValido)
    {{-- Registro órfão: tipo não está mais entre os tipos suportados.
         Não dá pra editar (validação do update rejeita), só remover. --}}
    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
        <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 px-4 py-3 rounded mb-4">
            <p class="font-semibold flex items-center gap-2">
                <i class="ri-error-warning-line text-lg"></i> Registro órfão detectado
            </p>
            <p class="text-sm mt-1">
                A automação <strong>#{{ $automacao->id }}</strong> está com tipo
                <code class="bg-white/60 px-1 rounded">{{ $automacao->tipo ?: 'NULL' }}</code>,
                que não corresponde a nenhum tipo conhecido pelo sistema.
                Provavelmente é resíduo de uma versão anterior. Pode remover sem perda.
            </p>
        </div>
        <dl class="text-sm text-slate-600 mb-4 grid grid-cols-2 gap-2">
            <div><dt class="text-xs uppercase text-slate-400">Nome</dt><dd>{{ $automacao->nome ?: '—' }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-400">Total enviados</dt><dd>{{ $automacao->total_enviados }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-400">Última execução</dt><dd>{{ $automacao->ultima_execucao?->format('d/m/Y H:i') ?: 'nunca' }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-400">Criado em</dt><dd>{{ $automacao->created_at?->format('d/m/Y H:i') ?: '—' }}</dd></div>
        </dl>
        <div class="flex gap-2">
            <form action="{{ route('super.automacoes.destroy', $automacao) }}" method="POST" onsubmit="return confirm('Remover este registro órfão? Não há mais como recuperar.')">
                @csrf @method('DELETE')
                <button class="px-5 py-2 bg-rose-600 text-white rounded-lg font-semibold">
                    <i class="ri-delete-bin-line"></i> Remover registro órfão
                </button>
            </form>
            <a href="{{ route('super.automacoes.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Voltar</a>
        </div>
    </div>
@else
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <form method="POST" action="{{ $automacao->exists ? route('super.automacoes.update', $automacao) : route('super.automacoes.store') }}">
        @csrf
        @if ($automacao->exists) @method('PUT') @endif
        @unless ($automacao->exists)
            <input type="hidden" name="tipo" value="{{ $automacao->tipo }}">
        @endunless

        <div class="bg-slate-50 rounded-lg p-3 mb-4 text-sm">
            <p class="font-semibold">{{ $tipoLabel }}</p>
        </div>

        @if ($automacao->exists && (empty($automacao->nome) || empty($automacao->mensagem)))
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 px-3 py-2 mb-4 text-xs rounded">
                <i class="ri-information-line"></i>
                Este registro estava com campos em branco. Carregamos os valores padrão pra você revisar e salvar.
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium">Nome interno *</label>
                <input type="text" name="nome" required value="{{ old('nome', $nomeDefault) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>

            <div>
                <label class="text-sm font-medium">Mensagem WhatsApp *</label>
                <textarea name="mensagem" required rows="8" class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">{{ old('mensagem', $mensagemDefault) }}</textarea>
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
                @php $ativoDefault = $automacao->exists ? $automacao->ativo : true; @endphp
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $ativoDefault) ? 'checked' : '' }} class="rounded">
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
@endif
@endsection
