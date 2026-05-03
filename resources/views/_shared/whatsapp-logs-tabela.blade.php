<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr>
                <th class="text-left p-3">Quando</th>
                @if ($comEmpresa)
                    <th class="text-left p-3">Empresa</th>
                @endif
                <th class="text-left p-3">Cliente / Telefone</th>
                <th class="text-left p-3">Evento</th>
                <th class="text-left p-3">Origem</th>
                <th class="text-left p-3">Mensagem</th>
                <th class="text-center p-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($envios as $env)
                <tr class="hover:bg-slate-50 align-top">
                    <td class="p-3 whitespace-nowrap text-xs text-slate-600">
                        {{ $env->created_at->format('d/m/y') }}<br>
                        <span class="text-slate-400">{{ $env->created_at->format('H:i:s') }}</span>
                    </td>
                    @if ($comEmpresa)
                        <td class="p-3 text-xs">
                            {{ $env->empresa->nome ?? '—' }}
                        </td>
                    @endif
                    <td class="p-3 text-xs">
                        @if ($env->cliente)
                            <p class="font-medium text-slate-800">{{ $env->cliente->nome }}</p>
                            <p class="text-slate-500 font-mono">{{ $env->telefone }}</p>
                        @else
                            <p class="font-mono text-slate-700">{{ $env->telefone }}</p>
                            <p class="text-[10px] text-slate-400">cliente não cadastrado</p>
                        @endif
                    </td>
                    <td class="p-3 text-xs">
                        <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $env->evento }}</span>
                        @if ($env->provider)
                            <p class="text-[10px] text-slate-400 mt-1">via {{ $env->provider }}</p>
                        @endif
                    </td>
                    <td class="p-3 text-xs">
                        <span @class([
                            'px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide',
                            'bg-purple-100 text-purple-700' => $env->origem === 'campanha',
                            'bg-blue-100 text-blue-700' => $env->origem === 'automacao',
                            'bg-emerald-100 text-emerald-700' => $env->origem === 'sistema',
                            'bg-amber-100 text-amber-700' => $env->origem === 'teste',
                            'bg-slate-100 text-slate-600' => !in_array($env->origem, ['campanha','automacao','sistema','teste']),
                        ])>{{ $env->origem }}</span>
                    </td>
                    <td class="p-3 text-xs max-w-md">
                        <div class="line-clamp-3 text-slate-600 whitespace-pre-line">{{ $env->mensagem }}</div>
                        @if ($env->erro)
                            <p class="text-rose-600 mt-1 text-[10px]"><i class="ri-error-warning-line"></i> {{ $env->erro }}</p>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        @if ($env->sucesso)
                            <span class="inline-flex items-center gap-1 text-emerald-600 text-xs">
                                <i class="ri-check-line"></i> Enviado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-rose-600 text-xs">
                                <i class="ri-close-line"></i> Falha
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $comEmpresa ? 7 : 6 }}" class="p-8 text-center text-slate-400">
                        <i class="ri-inbox-line text-3xl block mb-2"></i>
                        Nenhum envio registrado ainda.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
