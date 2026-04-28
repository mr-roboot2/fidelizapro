@extends('layouts.admin')
@section('title', 'Regras de pontuação')
@section('content')
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b border-slate-200 flex justify-between">
        <h2 class="font-semibold">Regras configuradas</h2>
        <a href="{{ route('admin.regras.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
            <i class="ri-add-line"></i> Nova regra
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left p-3">Nome</th>
                    <th class="text-left p-3">Tipo</th>
                    <th class="text-right p-3">Pontos/R$</th>
                    <th class="text-right p-3">Mult.</th>
                    <th class="text-right p-3">Pts fixos</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($regras as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-medium">{{ $r->nome }}</td>
                        <td class="p-3"><span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $r->tipo }}</span></td>
                        <td class="p-3 text-right">{{ $r->pontos_por_real ?? '—' }}</td>
                        <td class="p-3 text-right">{{ $r->multiplicador }}x</td>
                        <td class="p-3 text-right">{{ $r->pontos_fixos ?? '—' }}</td>
                        <td class="p-3 text-center">
                            @if ($r->ativo)<span class="text-emerald-600">●</span>@else<span class="text-slate-400">●</span>@endif
                        </td>
                        <td class="p-3 text-center">
                            <a href="{{ route('admin.regras.edit', $r) }}" class="text-indigo-600">Editar</a>
                            <form action="{{ route('admin.regras.destroy', $r) }}" method="POST" class="inline ml-2"
                                  onsubmit="return confirm('Excluir regra?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-400">Nenhuma regra cadastrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
