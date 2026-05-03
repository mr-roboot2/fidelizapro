@extends("layouts.super")
@section('title', 'Automações WhatsApp')
@section('content')
<div class="max-w-4xl">
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl p-5 mb-6">
        <h2 class="font-bold text-lg flex items-center gap-2">
            <i class="ri-magic-line"></i> Automações
        </h2>
        <p class="text-white/80 text-sm mt-1">
            Mensagens enviadas automaticamente em momentos chave: cadastro, aniversário, pós-compra, clientes inativos.
            Rodam diariamente às 09:00 (configurável no servidor).
        </p>
    </div>

    <div class="space-y-3">
        @foreach ($tipos as $tipo => $auto)
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold">{{ \App\Models\Automacao::TIPOS[$tipo] }}</h3>
                            @if ($auto->exists)
                                @if ($auto->ativo)
                                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Ativa</span>
                                @else
                                    <span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">Pausada</span>
                                @endif
                            @else
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Não configurada</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            @switch($tipo)
                                @case('boas_vindas') Disparado quando o cliente se cadastra @break
                                @case('aniversario') Disparado no dia do aniversário @break
                                @case('pontos_vencendo') {{ $auto->dias_offset ?? 7 }} dias antes dos pontos expirarem @break
                                @case('inativo_30d') Cliente sem comprar há 30 dias @break
                                @case('inativo_60d') Cliente sem comprar há 60 dias @break
                                @case('pos_compra') Após cada compra registrada @break
                                @case('agradecimento_resgate') Após resgate aprovado @break
                            @endswitch
                        </p>

                        <div class="mt-3 p-3 bg-slate-50 rounded text-xs text-slate-700 whitespace-pre-line break-words">{{ $auto->mensagem ?: '(sem mensagem)' }}</div>

                        @if ($auto->exists && $auto->total_enviados > 0)
                            <p class="text-xs text-slate-500 mt-2">
                                <i class="ri-send-plane-line"></i> {{ $auto->total_enviados }} mensagens enviadas
                                @if ($auto->ultima_execucao) • última execução {{ $auto->ultima_execucao->diffForHumans() }} @endif
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 shrink-0">
                        @if ($auto->exists)
                            <a href="{{ route('super.automacoes.edit', $auto) }}" class="text-xs px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded">Editar</a>
                            <form action="{{ route('super.automacoes.toggle', $auto) }}" method="POST">
                                @csrf
                                <button class="text-xs px-3 py-1.5 {{ $auto->ativo ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} rounded w-full">
                                    {{ $auto->ativo ? 'Pausar' : 'Ativar' }}
                                </button>
                            </form>
                            @if (in_array($tipo, ['aniversario','pontos_vencendo','inativo_30d','inativo_60d']))
                                <form action="{{ route('super.automacoes.executar', $auto) }}" method="POST" onsubmit="return confirm('Executar agora? Vai enviar mensagens reais.')">
                                    @csrf
                                    <button class="text-xs px-3 py-1.5 bg-purple-100 text-purple-700 rounded w-full">
                                        <i class="ri-play-line"></i> Executar agora
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('super.automacoes.create', ['tipo' => $tipo]) }}"
                               class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded">
                                <i class="ri-add-line"></i> Configurar
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 p-4 bg-slate-50 rounded-xl text-sm">
        <p class="font-semibold text-slate-700 mb-2">⏰ Como funciona o agendamento</p>
        <p class="text-slate-600 text-xs">As automações em batch (aniversário, inativos, pontos vencendo) rodam via cron do sistema. Para que rode automaticamente, configure no servidor:</p>
        <pre class="mt-2 bg-white p-2 rounded border border-slate-200 text-xs">* * * * * cd /path/fidelizapro && php artisan schedule:run >> /dev/null 2>&1</pre>
        <p class="text-slate-600 text-xs mt-2">Em dev (XAMPP), você pode disparar manualmente clicando em "Executar agora" ou rodando: <code>php artisan automacoes:executar</code></p>
    </div>
</div>
@endsection
