@extends('layouts.super')
@section('title', 'Templates WhatsApp')
@section('content')

<div class="flex justify-end mb-3">
    <a href="{{ route('super.whatsapp-templates.meta') }}"
       class="text-sm bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2 rounded-lg font-medium">
        <i class="ri-list-check-2"></i> Ver templates registrados na Meta
    </a>
</div>

@php $marcadorEmpresa = '{{empresa}}'; @endphp
<div class="bg-rose-50 border border-rose-100 rounded-xl p-4 mb-5 text-sm text-rose-900">
    <p class="font-semibold mb-1"><i class="ri-information-line"></i> Templates globais</p>
    <p class="text-rose-800">
        Esses templates são compartilhados por todas as empresas. Use <code>{{ $marcadorEmpresa }}</code> nos parâmetros pra que cada empresa apareça com seu nome no template.
    </p>
</div>

<div class="space-y-3">
    @foreach ($eventos as $slug => $def)
        @php $tpl = $configurados[$slug] ?? null; @endphp
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('super.whatsapp-templates.update', $slug) }}">
                @csrf @method('PUT')

                <div class="p-5 flex items-start gap-4 flex-wrap">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center flex-shrink-0">
                        <i class="ri-message-3-line text-rose-600 text-xl"></i>
                    </div>

                    <div class="flex-1 min-w-[240px]">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-slate-800">{{ $def['rotulo'] }}</h3>
                            @if ($tpl && $tpl->ativo)
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">Configurado</span>
                            @elseif ($tpl)
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">Inativo</span>
                            @else
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Texto livre</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $def['descricao'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">
                            Parâmetros enviados na ordem:
                            @foreach ($def['parametros'] as $i => $p)
                                @php $marker = '{{'.($i+1).'}}'; @endphp
                                <code class="bg-slate-100 px-1 rounded">{{ $marker }} = {{ $p }}</code>{!! !$loop->last ? ' &middot; ' : '' !!}
                            @endforeach
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1 italic">Exemplo: "{{ $def['exemplo'] }}"</p>
                    </div>

                    <div class="flex flex-col gap-2 w-full md:w-72">
                        <input type="text" name="nome_template" value="{{ old('nome_template', $tpl?->nome_template) }}"
                               placeholder="ex: codigo_otp"
                               class="px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                        <div class="flex gap-2">
                            <select name="idioma" class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm">
                                <option value="pt_BR" @selected(($tpl?->idioma ?? 'pt_BR') === 'pt_BR')>pt_BR (português)</option>
                                <option value="en_US" @selected(($tpl?->idioma ?? '') === 'en_US')>en_US (inglês)</option>
                                <option value="es" @selected(($tpl?->idioma ?? '') === 'es')>es (espanhol)</option>
                            </select>
                            <label class="flex items-center gap-1.5 text-xs px-2 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" name="ativo" value="1" {{ ($tpl?->ativo ?? true) ? 'checked' : '' }}>
                                <span>Ativo</span>
                            </label>
                        </div>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
