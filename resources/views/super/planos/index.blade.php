@extends('layouts.super')
@section('title', 'Planos do SaaS')
@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('super.planos.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-lg text-sm">
        <i class="ri-add-line"></i> Novo plano
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @forelse ($planos as $p)
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between mb-2">
                <h3 class="font-bold text-lg">{{ $p->nome }}</h3>
                @if (!$p->ativo)<span class="text-xs bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full">Inativo</span>@endif
            </div>
            <p class="text-3xl font-bold text-rose-600 mb-3">R$ {{ number_format($p->preco_mensal, 2, ',', '.') }}<span class="text-xs text-slate-500 font-normal">/mês</span></p>

            <ul class="text-xs text-slate-600 space-y-1 mb-4">
                <li>👥 {{ $p->limite_clientes ? number_format($p->limite_clientes, 0, ',', '.').' clientes' : 'Clientes ilimitados' }}</li>
                <li>🛒 {{ $p->limite_compras_mes ? number_format($p->limite_compras_mes, 0, ',', '.').' compras/mês' : 'Compras ilimitadas' }}</li>
                <li>🎁 {{ $p->limite_recompensas ? $p->limite_recompensas.' recompensas' : 'Recompensas ilimitadas' }}</li>
                <li>🤝 {{ $p->limite_parceiros ? $p->limite_parceiros.' parceiros' : ($p->parceiros_disponivel ? 'Parceiros ilimitados' : 'Sem parceiros') }}</li>
                @if ($p->automacoes_disponivel)<li>✅ Automações WhatsApp</li>@else<li class="text-slate-400">❌ Sem automações</li>@endif
                @if ($p->white_label_disponivel)<li>✅ White label PWA</li>@endif
            </ul>

            <p class="text-xs text-slate-500 mb-3">{{ $p->empresas_count }} empresa(s) neste plano</p>
            <div class="flex gap-2">
                <a href="{{ route('super.planos.edit', $p) }}" class="flex-1 text-center text-sm bg-slate-100 py-1.5 rounded">Editar</a>
                <form action="{{ route('super.planos.destroy', $p) }}" method="POST" onsubmit="return confirm('Excluir plano?')">
                    @csrf @method('DELETE')
                    <button class="text-sm bg-rose-100 text-rose-700 py-1.5 px-3 rounded">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-slate-400 col-span-full text-center py-10">Nenhum plano cadastrado.</p>
    @endforelse
</div>
<div class="mt-4">{{ $planos->links() }}</div>
@endsection
