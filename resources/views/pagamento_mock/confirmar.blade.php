<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pagamento — {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@@4.2.0/fonts/remixicon.css">
</head>
<body class="bg-slate-100 min-h-screen p-4">
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white text-3xl mb-3">
                    <i class="ri-bank-card-line"></i>
                </div>
                <h1 class="text-xl font-bold">Pagamento de mensalidade</h1>
                <p class="text-sm text-slate-500">{{ $cobranca->assinatura->empresa->nome }}</p>
            </div>

            <div class="bg-slate-50 rounded-lg p-4 mb-6 text-sm space-y-2">
                <div class="flex justify-between"><span class="text-slate-500">Plano</span><span class="font-semibold">{{ $cobranca->assinatura->plano->nome }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Vencimento</span><span>{{ $cobranca->vencimento->format('d/m/Y') }}</span></div>
                <div class="flex justify-between text-lg pt-2 border-t border-slate-200">
                    <span class="font-bold">Total</span>
                    <span class="font-bold text-emerald-600">R$ {{ number_format($cobranca->valor, 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-xs text-amber-700">
                <i class="ri-information-line"></i> <strong>Modo MOCK:</strong> isso simula um gateway real. Em produção (Asaas), o cliente vê uma tela de PIX/boleto/cartão.
            </div>

            <form action="{{ route('pagamento.mock.confirmar', $cobranca->id) }}" method="POST">
                @csrf
                <button class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg font-bold">
                    <i class="ri-check-line"></i> Confirmar pagamento
                </button>
            </form>
        </div>
    </div>
</body>
</html>
