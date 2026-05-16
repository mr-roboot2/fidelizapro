<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro indisponível - {{ $sistema?->nome_sistema ?? 'FidelizaPro' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ "https://cdn.jsdelivr.net/npm/remixicon" }}@4.2.0/fonts/remixicon.css">
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
            <i class="ri-time-line text-3xl text-amber-600"></i>
        </div>
        <h1 class="text-xl font-bold text-slate-800 mb-2">Cadastro indisponível no momento</h1>
        <p class="text-slate-500 text-sm mb-6">
            Estamos pausando novos cadastros temporariamente. Entre em contato
            @if ($sistema?->email_suporte)
                pelo e-mail <a href="mailto:{{ $sistema->email_suporte }}" class="text-indigo-600 hover:underline">{{ $sistema->email_suporte }}</a>
            @endif
            pra saber quando vamos voltar a aceitar novas empresas.
        </p>
        <a href="{{ route('admin.login') }}" class="inline-block px-6 py-2.5 rounded-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
            Ir para o login
        </a>
    </div>
</body>
</html>
