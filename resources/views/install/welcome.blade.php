@extends('install.layout', ['step' => 'welcome'])

@section('title', 'Requisitos')

@section('content')
    <h2 class="text-xl font-bold text-slate-800 mb-1">Verificação de requisitos</h2>
    <p class="text-slate-500 text-sm mb-6">Antes de começar, vamos checar se o servidor atende aos requisitos mínimos.</p>

    <div class="space-y-2 mb-6">
        @foreach($checks as $label => $ok)
            <div class="flex items-center justify-between px-4 py-2.5 rounded-lg border {{ $ok ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200' }}">
                <span class="text-sm text-slate-700">{{ $label }}</span>
                @if($ok)
                    <span class="text-emerald-600 text-sm font-medium flex items-center gap-1"><i class="ri-check-line"></i> OK</span>
                @else
                    <span class="text-rose-600 text-sm font-medium flex items-center gap-1"><i class="ri-close-line"></i> Falhou</span>
                @endif
            </div>
        @endforeach
    </div>

    @if(!$allOk)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 mb-4 rounded-lg text-sm">
            Resolva os itens acima antes de prosseguir. No CloudPanel/Linux, geralmente é
            <code class="bg-amber-100 px-1 rounded">composer install --no-dev --optimize-autoloader</code>
            e <code class="bg-amber-100 px-1 rounded">chmod -R 775 storage bootstrap/cache</code>.
        </div>
    @endif

    <div class="flex justify-end">
        <a href="{{ url('/install/database') }}"
           class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-semibold text-white {{ $allOk ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700' : 'bg-slate-300 pointer-events-none' }}">
            Continuar <i class="ri-arrow-right-line"></i>
        </a>
    </div>
@endsection
