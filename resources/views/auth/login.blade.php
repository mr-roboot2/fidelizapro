<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FidelizaPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold mb-3">F</div>
            <h1 class="text-2xl font-bold text-slate-800">FidelizaPro</h1>
            <p class="text-slate-500 text-sm">Painel Administrativo</p>
        </div>

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 mb-4 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
                <div class="relative">
                    <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="email" name="email" required value="{{ old('email') }}"
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                <div class="relative">
                    <i class="ri-lock-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="password" name="password" required
                           class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
            <label class="flex items-center text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
                <span class="ml-2">Lembrar de mim</span>
            </label>
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2.5 rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition">
                Entrar
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500">
            Acesso de cliente? <a href="/app/" class="text-indigo-600 hover:underline font-medium">App do cliente</a>
        </div>

        <div class="mt-6 p-3 bg-slate-50 rounded-lg text-xs text-slate-600">
            <p class="font-semibold mb-1">Acessos de teste:</p>
            <p>admin@pao-quente.com / password</p>
            <p>admin@beleza-cia.com / password</p>
            <p>admin@sabor-da-casa.com / password</p>
        </div>
    </div>
</body>
</html>
