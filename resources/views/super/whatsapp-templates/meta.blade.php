@extends('layouts.super')
@section('title', 'Templates registrados na Meta')
@section('content')

<div class="bg-white rounded-xl shadow-sm">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
        <div>
            <h2 class="font-semibold flex items-center gap-2">
                <i class="ri-meta-line text-indigo-600"></i> Templates na sua WABA
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">WABA ID: <code class="bg-slate-100 px-1 rounded font-mono text-[10px]">{{ $waba_id }}</code> &middot; lista direto da API da Meta</p>
        </div>
        <a href="{{ route('super.whatsapp-templates.index') }}" class="text-sm text-slate-600 hover:underline">
            <i class="ri-arrow-left-line"></i> Voltar
        </a>
    </div>

    @if (empty($templates))
        <div class="p-10 text-center">
            <p class="text-slate-500 font-medium">Nenhum template encontrado.</p>
        </div>
    @else
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3 text-left">Nome</th>
                    <th class="px-3 py-3 text-left">Categoria</th>
                    <th class="px-3 py-3 text-left">Idioma</th>
                    <th class="px-6 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($templates as $tpl)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 font-mono font-semibold text-slate-800">{{ $tpl['name'] }}</td>
                        <td class="px-3 py-3 text-xs text-slate-600">{{ $tpl['category'] ?? '—' }}</td>
                        <td class="px-3 py-3"><code class="bg-slate-100 px-2 py-0.5 rounded text-xs font-mono">{{ $tpl['language'] }}</code></td>
                        <td class="px-6 py-3">
                            @php
                                $st = strtoupper($tpl['status'] ?? '');
                                $cor = match ($st) {
                                    'APPROVED' => 'bg-emerald-100 text-emerald-700',
                                    'PENDING'  => 'bg-amber-100 text-amber-700',
                                    'REJECTED' => 'bg-rose-100 text-rose-700',
                                    default    => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $cor }}">{{ $st }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
