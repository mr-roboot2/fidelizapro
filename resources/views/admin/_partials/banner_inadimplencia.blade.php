@php
    if (!isset($empresaAtiva)) return;
    $status = $empresaAtiva->statusInadimplencia();
    if (in_array($status, ['em_dia', 'sem_assinatura'])) return;
    $dias = $empresaAtiva->diasAtraso();

    $cfgPorStatus = [
        'trial' => [
            'cor'    => 'bg-blue-50 border-blue-200 text-blue-800',
            'icone'  => 'ri-time-line',
            'titulo' => 'Você está no período de teste',
            'msg'    => 'Aproveite o trial gratuito. Faça upgrade quando quiser pra manter tudo ativo.',
        ],
        'aviso' => [
            'cor'    => 'bg-amber-50 border-amber-200 text-amber-800',
            'icone'  => 'ri-alert-line',
            'titulo' => "Sua cobrança venceu há {$dias} ".($dias === 1 ? 'dia' : 'dias'),
            'msg'    => 'Pague pra evitar bloqueio do sistema em até 7 dias.',
        ],
        'bloqueio_parcial' => [
            'cor'    => 'bg-orange-100 border-orange-300 text-orange-900',
            'icone'  => 'ri-error-warning-line',
            'titulo' => "Cobrança em atraso há {$dias} dias",
            'msg'    => 'Os recursos avançados (Roleta, Sorteios, WhatsApp, Parceiros) estão bloqueados. Regularize pra liberar.',
        ],
        'bloqueio_total' => [
            'cor'    => 'bg-rose-100 border-rose-300 text-rose-900',
            'icone'  => 'ri-close-circle-line',
            'titulo' => $dias > 0 ? "Cobrança em atraso há {$dias} dias" : 'Assinatura suspensa',
            'msg'    => 'O sistema está bloqueado. Você só consegue acessar "Meu plano" pra regularizar.',
        ],
    ];

    $cfg = $cfgPorStatus[$status] ?? null;
    if (!$cfg) return;
@endphp

<div class="border-b {{ $cfg['cor'] }} px-6 py-3 flex items-center justify-between gap-4 text-sm">
    <div class="flex items-center gap-3">
        <i class="{{ $cfg['icone'] }} text-xl"></i>
        <div>
            <p class="font-semibold">{{ $cfg['titulo'] }}</p>
            <p class="text-xs opacity-90">{{ $cfg['msg'] }}</p>
        </div>
    </div>
    <a href="{{ route('admin.meu-plano.index') }}" class="shrink-0 px-3 py-1.5 bg-white/60 hover:bg-white rounded-lg text-xs font-semibold transition">
        Ver meu plano
    </a>
</div>
