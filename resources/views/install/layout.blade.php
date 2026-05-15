<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Instalação') - FidelizaPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ "https://cdn.jsdelivr.net/npm/remixicon" }}@4.2.0/fonts/remixicon.css">
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen p-4 py-8">
    <div class="max-w-3xl mx-auto">

        <div class="text-center mb-6 text-white">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-white text-2xl font-bold mb-2">F</div>
            <h1 class="text-2xl font-bold">FidelizaPro</h1>
            <p class="text-white/80 text-sm">Instalador</p>
        </div>

        @php
            $steps = [
                ['key' => 'welcome',  'label' => 'Requisitos'],
                ['key' => 'database', 'label' => 'Banco'],
                ['key' => 'app',      'label' => 'Aplicação'],
                ['key' => 'admin',    'label' => 'Super Admin'],
                ['key' => 'complete', 'label' => 'Pronto'],
            ];
            $current = $step ?? 'welcome';
            $currentIdx = collect($steps)->search(fn($s) => $s['key'] === $current);
        @endphp

        <div class="bg-white/10 backdrop-blur rounded-xl p-3 mb-6">
            <div class="flex items-center justify-between">
                @foreach($steps as $i => $s)
                    @php $done = $i < $currentIdx; $active = $i === $currentIdx; @endphp
                    <div class="flex items-center flex-1 {{ $i === count($steps)-1 ? '' : '' }}">
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $done ? 'bg-emerald-500 text-white' : ($active ? 'bg-white text-indigo-700' : 'bg-white/30 text-white') }}">
                                @if($done)<i class="ri-check-line"></i>@else{{ $i+1 }}@endif
                            </div>
                            <span class="text-[11px] mt-1 text-white/90">{{ $s['label'] }}</span>
                        </div>
                        @if($i !== count($steps)-1)
                            <div class="h-1 flex-1 mx-1 rounded {{ $done ? 'bg-emerald-500' : 'bg-white/30' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 mb-4 rounded-lg text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>

        <p class="text-center text-white/70 text-xs mt-6">
            FidelizaPro &middot; SaaS de fidelização multitenancy
        </p>
    </div>
</body>
</html>
