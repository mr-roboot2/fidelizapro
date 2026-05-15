<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validar cupom - {{ $parceiro->nome }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css"
          integrity="sha384-iQsqPTYE5qeK3iqIgOg+9OkOd3S5YmN2EZEmGlhqDbxd7ZaJRWiNiBoxet73ers7"
          crossorigin="anonymous">
</head>
<body class="bg-slate-100 min-h-screen p-4">
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 bg-gradient-to-br from-indigo-600 to-purple-600 text-white">
                @if ($parceiro->logo)
                    <img src="{{ asset('storage/'.$parceiro->logo) }}" class="w-16 h-16 rounded-xl object-cover mb-3">
                @else
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-3xl">
                        <i class="ri-building-line"></i>
                    </div>
                @endif
                <h1 class="text-xl font-bold mt-2">{{ $parceiro->nome }}</h1>
                <p class="text-white/80 text-sm">Validar cupom de cliente</p>
            </div>

            <div class="p-6">
                @if (!empty($sucesso))
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 text-emerald-700 font-bold mb-2">
                            <i class="ri-check-double-line text-2xl"></i> Cupom válido!
                        </div>
                        <dl class="space-y-1 text-sm">
                            <div><dt class="text-slate-500 inline">Cliente:</dt> <dd class="inline font-semibold">{{ $cupom->cliente->nome }}</dd></div>
                            <div><dt class="text-slate-500 inline">Benefício:</dt> <dd class="inline font-semibold">{{ $cupom->beneficio->nome }}</dd></div>
                            <div><dt class="text-slate-500 inline">Tipo:</dt> <dd class="inline text-emerald-700">{{ $cupom->beneficio->descricaoTipo() }}</dd></div>
                            <div><dt class="text-slate-500 inline">Validado em:</dt> <dd class="inline">{{ $cupom->usado_em->format('d/m/Y H:i') }}</dd></div>
                        </dl>
                        @if ($cupom->beneficio->condicoes)
                            <div class="mt-3 p-2 bg-white rounded text-xs text-slate-600">
                                <strong>Condições:</strong> {{ $cupom->beneficio->condicoes }}
                            </div>
                        @endif
                        <a href="{{ route('parceiro.publico', $parceiro->validacao_secret) }}" class="mt-4 block text-center text-sm bg-emerald-600 text-white py-2 rounded-lg">
                            Validar outro cupom
                        </a>
                    </div>
                @elseif (!empty($erro))
                    <div class="bg-rose-50 border border-rose-200 rounded-lg p-4 mb-4">
                        <p class="text-rose-700 font-semibold"><i class="ri-error-warning-line"></i> {{ $erro }}</p>
                        @if (!empty($codigo_tentado))<p class="text-xs text-rose-600 mt-1">Código tentado: <code>{{ $codigo_tentado }}</code></p>@endif
                    </div>
                @endif

                <form method="POST" action="{{ route('parceiro.validar', $parceiro->validacao_secret) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-medium">Código do cupom</label>
                        <input type="text" name="codigo" required minlength="6" maxlength="12"
                               autofocus autocomplete="off" autocapitalize="characters"
                               style="text-transform: uppercase"
                               placeholder="ABC123XYZ"
                               class="mt-1 w-full px-4 py-3 border border-slate-300 rounded-lg text-center text-2xl font-mono tracking-widest">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Observação (opcional)</label>
                        <input type="text" name="observacao" placeholder="Ex: Atendido por João"
                               class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <button class="w-full py-3 bg-indigo-600 text-white rounded-lg font-bold">
                        <i class="ri-check-line"></i> Validar cupom
                    </button>
                </form>

                <div class="mt-6 p-3 bg-slate-50 rounded text-xs text-slate-600">
                    <p><strong>Como funciona:</strong></p>
                    <ol class="list-decimal list-inside mt-1 space-y-1">
                        <li>Cliente apresenta o código do cupom (do app dele)</li>
                        <li>Você digita aqui e clica em validar</li>
                        <li>Após validação, o cupom é marcado como usado</li>
                    </ol>
                </div>
            </div>
        </div>
        <p class="text-center text-xs text-slate-400 mt-4">Powered by {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</p>
    </div>
</body>
</html>
