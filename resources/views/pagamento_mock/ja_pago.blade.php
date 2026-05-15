<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pagamento confirmado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@@4.2.0/fonts/remixicon.css">
</head>
<body class="bg-slate-100 min-h-screen p-4">
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white text-4xl mb-4">
                <i class="ri-check-double-line"></i>
            </div>
            <h1 class="text-2xl font-bold text-emerald-700">Pagamento confirmado!</h1>
            <p class="text-slate-600 mt-2">{{ $cobranca->assinatura->empresa->nome }}</p>
            <p class="text-3xl font-bold text-emerald-600 mt-4">R$ {{ number_format($cobranca->valor, 2, ',', '.') }}</p>
            <p class="text-xs text-slate-500 mt-2">Pago em {{ $cobranca->pago_em->format('d/m/Y H:i') }}</p>
            <p class="text-sm text-slate-500 mt-4">Próximo vencimento: {{ $cobranca->assinatura->proximo_vencimento->format('d/m/Y') }}</p>
        </div>
    </div>
</body>
</html>
