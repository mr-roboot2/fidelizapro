<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    @if (!empty($sistema?->faviconUrl()))
        <link rel="icon" href="{{ $sistema->faviconUrl() }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ "https://cdn.jsdelivr.net/npm/remixicon" }}@4.2.0/fonts/remixicon.css">
    @inject('_captcha', 'App\Services\CaptchaService')
    @if ($_captcha->isEnabled())
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>
@php
    $cor1 = $sistema->cor_primaria ?? '#6366f1';
    $cor2 = $sistema->cor_secundaria ?? '#8b5cf6';
    $nome = $sistema->nome_sistema ?? 'FidelizaPro';
@endphp
<body class="min-h-screen flex items-center justify-center p-4 text-white" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-slate-800">
        <div class="text-center mb-8">
            @if ($sistema?->logoUrl())
                <img src="{{ $sistema->logoUrl() }}" alt="{{ $nome }}" class="w-16 h-16 mx-auto rounded-2xl object-contain mb-3 p-2" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
            @else
                <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center text-white text-3xl font-bold mb-3" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">{{ strtoupper(substr($nome, 0, 1)) }}</div>
            @endif
            <h1 class="text-2xl font-bold text-slate-800">{{ $nome }}</h1>
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
            @if ($_captcha->isEnabled())
                <div class="cf-turnstile" data-sitekey="{{ $_captcha->siteKey() }}"></div>
            @endif
            <button type="submit" class="w-full text-white py-2.5 rounded-lg font-semibold transition hover:opacity-95" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
                Entrar
            </button>
        </form>

        @if ($sistema?->cadastro_publico_ativo ?? true)
            <div class="mt-6 pt-4 border-t border-slate-100 text-center text-sm text-slate-600">
                Ainda não tem conta?
                <a href="{{ route('cadastro.empresa.form') }}" class="text-indigo-600 hover:underline font-semibold">Criar grátis</a>
            </div>
        @endif
        <div class="mt-3 text-center text-xs text-slate-500">
            Acesso de cliente? <a href="/app/" class="text-indigo-600 hover:underline font-medium">App do cliente</a>
        </div>

        @if (app()->environment('local'))
        <div class="mt-6 p-3 bg-slate-50 rounded-lg text-xs text-slate-600">
            <p class="font-semibold mb-1">Acessos de teste (apenas local):</p>
            <p>admin@pao-quente.com / password</p>
            <p>admin@beleza-cia.com / password</p>
            <p>admin@sabor-da-casa.com / password</p>
        </div>
        @endif
    </div>
</body>
</html>
