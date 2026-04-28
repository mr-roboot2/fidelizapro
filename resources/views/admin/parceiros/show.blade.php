@extends('layouts.admin')
@section('title', $parceiro->nome)
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna esquerda: dados do parceiro -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        @if ($parceiro->logo)
            <img src="{{ asset('storage/'.$parceiro->logo) }}" class="w-24 h-24 rounded-xl object-cover mb-3">
        @else
            <div class="w-24 h-24 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center text-white text-3xl mb-3">
                <i class="ri-building-line"></i>
            </div>
        @endif
        <h2 class="font-bold text-lg">{{ $parceiro->nome }}</h2>
        @if ($parceiro->categoria)
            <p class="text-xs text-slate-500">{{ $parceiro->categoria }}</p>
        @endif
        <p class="text-sm text-slate-700 mt-2">{{ $parceiro->descricao }}</p>

        <dl class="mt-4 space-y-1 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Telefone</dt><dd>{{ $parceiro->telefone ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">E-mail</dt><dd class="truncate ml-2">{{ $parceiro->email ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Endereço</dt><dd class="text-right ml-2">{{ $parceiro->endereco ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Cupons gerados</dt><dd class="font-semibold">{{ $totalCupons }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Cupons usados</dt><dd class="font-semibold text-emerald-600">{{ $cuponsUsados }}</dd></div>
        </dl>

        <div class="mt-5 flex gap-2">
            <a href="{{ route('admin.parceiros.edit', $parceiro) }}" class="flex-1 text-center text-sm bg-slate-100 py-2 rounded-lg">
                <i class="ri-edit-line"></i> Editar
            </a>
            <form action="{{ route('admin.parceiros.destroy', $parceiro) }}" method="POST" onsubmit="return confirm('Excluir parceiro e todos os benefícios/cupons?')">
                @csrf @method('DELETE')
                <button class="text-sm bg-rose-100 text-rose-700 py-2 px-3 rounded-lg">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </form>
        </div>

        <!-- URL de validação para o parceiro -->
        <div class="mt-5 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-xs font-semibold text-amber-800 mb-1">
                <i class="ri-link"></i> Link para o parceiro validar cupons
            </p>
            <code class="text-[11px] text-amber-700 break-all block bg-white p-2 rounded mt-1">{{ $parceiro->urlValidacao() }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $parceiro->urlValidacao() }}'); this.textContent='✓ Copiado'"
                    class="mt-2 text-xs bg-amber-200 text-amber-800 px-2 py-1 rounded">
                Copiar link
            </button>
        </div>
    </div>

    <!-- Coluna direita: benefícios -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">Benefícios oferecidos</h3>
            <a href="{{ route('admin.beneficios.create', $parceiro) }}"
               class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-lg">
                <i class="ri-add-line"></i> Novo benefício
            </a>
        </div>

        <div class="space-y-3">
            @forelse ($parceiro->beneficios as $b)
                <div class="border border-slate-200 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-sm">{{ $b->nome }}</h4>
                                @if ($b->destaque)<span class="text-xs bg-amber-100 text-amber-700 px-2 rounded-full">Destaque</span>@endif
                                @if (!$b->ativo)<span class="text-xs bg-slate-200 text-slate-600 px-2 rounded-full">Inativo</span>@endif
                            </div>
                            <p class="text-xs text-emerald-700 font-semibold mt-1">{{ $b->descricaoTipo() }}</p>
                            @if ($b->descricao)
                                <p class="text-xs text-slate-600 mt-1">{{ $b->descricao }}</p>
                            @endif
                            <div class="flex gap-3 text-xs text-slate-500 mt-2">
                                @if ($b->valido_ate)<span>até {{ $b->valido_ate->format('d/m/Y') }}</span>@endif
                                @if ($b->limite_por_cliente)<span>{{ $b->limite_por_cliente }}/cliente</span>@endif
                                <span>{{ $b->total_resgatados }} resgatados</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 shrink-0">
                            <a href="{{ route('admin.beneficios.edit', $b) }}" class="text-xs text-indigo-600">Editar</a>
                            <form action="{{ route('admin.beneficios.destroy', $b) }}" method="POST" onsubmit="return confirm('Excluir?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-rose-600">Excluir</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400 text-center py-8">Nenhum benefício cadastrado ainda.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
