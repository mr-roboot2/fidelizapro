<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Já instalado - FidelizaPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-4">
            <span class="text-3xl text-emerald-600">&#10003;</span>
        </div>
        <h1 class="text-xl font-bold text-slate-800 mb-2">FidelizaPro já instalado</h1>
        <p class="text-slate-500 text-sm mb-6 text-left">
            O instalador foi travado após a configuração. Para reabrir,
            remova <strong>ambos</strong> os sinais de instalação:
        </p>
        <pre class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-left font-mono text-slate-700 overflow-x-auto mb-6">rm storage/installed.lock
php artisan migrate:fresh --force
php artisan optimize:clear</pre>
        <p class="text-[11px] text-slate-400 mb-6">
            O <code>migrate:fresh</code> DROP todas as tabelas e zera a flag
            <code>configuracoes_sistema.instalado_em</code> (defesa em
            profundidade contra remoção indevida do lock).
        </p>
        <a href="/admin/login" class="inline-block px-6 py-2.5 rounded-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
            Ir para login
        </a>
    </div>
</body>
</html>
