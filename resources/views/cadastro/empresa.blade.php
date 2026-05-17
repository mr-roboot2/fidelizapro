<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cadastro - {{ $sistema->nome_sistema ?? 'FidelizaPro' }}</title>
    @if ($sistema?->faviconUrl())
        <link rel="icon" href="{{ $sistema->faviconUrl() }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ "https://cdn.jsdelivr.net/npm/remixicon" }}@4.2.0/fonts/remixicon.css"
          integrity="sha384-6FSSi597BTd6QcnsBNoLclRKxTOyyYqkaucRjFgCNr8wHVCp0COLClSPY4Vy/bjh"
          crossorigin="anonymous">
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
<body class="bg-slate-50 min-h-screen p-4 py-8">
    <div class="max-w-3xl mx-auto">
        <header class="text-center mb-8">
            @if ($sistema?->logoUrl())
                <img src="{{ $sistema->logoUrl() }}" alt="{{ $nome }}" class="w-16 h-16 mx-auto rounded-2xl object-contain mb-3 p-2" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
            @else
                <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center text-white text-3xl font-bold mb-3" style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">{{ strtoupper(substr($nome, 0, 1)) }}</div>
            @endif
            <h1 class="text-3xl font-bold text-slate-800">Crie sua conta {{ $nome }}</h1>
            <p class="text-slate-500 mt-2">
                <i class="ri-gift-line text-emerald-500"></i>
                Teste grátis por <strong>{{ $trial_dias }} dias</strong> · Sem cartão de crédito
            </p>
        </header>

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 mb-6">
                <p class="font-semibold text-rose-700 mb-1"><i class="ri-error-warning-line"></i> Corrija os campos abaixo:</p>
                <ul class="list-disc list-inside text-sm text-rose-700">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('cadastro.empresa.processar') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Dados da empresa --}}
            <section class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-lg text-slate-800 mb-1">Dados da empresa</h2>
                <p class="text-xs text-slate-500 mb-5">Como os clientes vão te conhecer.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nome do estabelecimento *</label>
                        <input type="text" name="nome" required maxlength="120"
                               value="{{ old('nome') }}"
                               placeholder="Ex: Pizzaria do João"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">URL personalizada</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 bg-slate-100 border border-r-0 border-slate-300 rounded-l-lg text-sm text-slate-500">/app/</span>
                            <input type="text" name="slug" maxlength="80" pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                                   value="{{ old('slug') }}"
                                   placeholder="pizzaria-do-joao"
                                   class="flex-1 px-4 py-2.5 border border-slate-300 rounded-r-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Vazio gera automaticamente. Só letras minúsculas, números e hífen.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">CNPJ</label>
                        <input type="text" name="cnpj" maxlength="18"
                               value="{{ old('cnpj') }}"
                               placeholder="00.000.000/0000-00"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-[11px] text-slate-500 mt-1">Opcional — usado pra emissão de cobranças.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Telefone *</label>
                        <input type="tel" name="telefone" required maxlength="20"
                               value="{{ old('telefone') }}"
                               placeholder="(11) 99999-9999"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">E-mail comercial *</label>
                        <input type="email" name="email" required maxlength="120"
                               value="{{ old('email') }}"
                               placeholder="contato@empresa.com"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                        <input type="text" name="endereco" maxlength="255"
                               value="{{ old('endereco') }}"
                               placeholder="Rua, número, bairro, cidade"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cor primária *</label>
                        <input type="color" name="cor_primaria" required
                               value="{{ old('cor_primaria', '#6366f1') }}"
                               class="w-full h-11 px-2 border border-slate-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cor secundária *</label>
                        <input type="color" name="cor_secundaria" required
                               value="{{ old('cor_secundaria', '#8b5cf6') }}"
                               class="w-full h-11 px-2 border border-slate-300 rounded-lg">
                    </div>
                </div>
            </section>

            {{-- Programa de fidelidade --}}
            <section class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-lg text-slate-800 mb-1">Programa de fidelidade</h2>
                <p class="text-xs text-slate-500 mb-5">Pode mudar tudo isso depois no painel.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Modelo *</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach (['pontos' => ['Pontos', 'ri-coin-line', 'Cliente acumula pontos e troca por prêmios.'], 'cashback' => ['Cashback', 'ri-money-dollar-circle-line', 'Cliente recebe % de volta pra usar como desconto.'], 'ambos' => ['Pontos + Cashback', 'ri-stack-line', 'Os dois juntos.']] as $valor => $info)
                                <label class="cursor-pointer">
                                    <input type="radio" name="modo_fidelidade" value="{{ $valor }}"
                                           class="peer sr-only"
                                           {{ old('modo_fidelidade', 'ambos') === $valor ? 'checked' : '' }}>
                                    <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition">
                                        <i class="{{ $info[1] }} text-2xl text-indigo-600 mb-2"></i>
                                        <p class="font-semibold text-sm text-slate-800">{{ $info[0] }}</p>
                                        <p class="text-[11px] text-slate-500 mt-1">{{ $info[2] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Pontos por R$ 1</label>
                            <input type="number" step="0.01" min="0" max="100" name="pontos_por_real"
                                   value="{{ old('pontos_por_real', 1) }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-[11px] text-slate-500 mt-1">Ex: 1 ponto a cada R$ 1.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cashback (%)</label>
                            <input type="number" step="0.1" min="0" max="100" name="cashback_percentual"
                                   value="{{ old('cashback_percentual', 5) }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-[11px] text-slate-500 mt-1">% do valor da compra.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Validade pontos (dias)</label>
                            <input type="number" min="30" max="3650" name="validade_pontos_dias"
                                   value="{{ old('validade_pontos_dias', 365) }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-[11px] text-slate-500 mt-1">Quanto tempo pontos duram.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Plano --}}
            <section class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-lg text-slate-800 mb-1">Escolha seu plano</h2>
                <p class="text-xs text-slate-500 mb-5">
                    <i class="ri-gift-line text-emerald-600"></i> Todos os planos vêm com <strong>{{ $trial_dias }} dias grátis</strong>. Sem cobrança até o trial acabar.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-{{ min($planos->count(), 3) }} gap-4">
                    @foreach ($planos as $plano)
                        <label class="cursor-pointer">
                            <input type="radio" name="plano_id" value="{{ $plano->id }}" required
                                   class="peer sr-only"
                                   {{ old('plano_id', $planos->first()->id) == $plano->id ? 'checked' : '' }}>
                            <div class="border-2 border-slate-200 rounded-xl p-5 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition h-full">
                                <p class="font-bold text-slate-800">{{ $plano->nome }}</p>
                                <p class="text-2xl font-bold text-indigo-600 mt-2">
                                    R$ {{ number_format($plano->preco_mensal, 2, ',', '.') }}
                                    <span class="text-xs font-normal text-slate-500">/mês</span>
                                </p>
                                @if ($plano->descricao)
                                    <p class="text-xs text-slate-600 mt-3 whitespace-pre-line">{{ $plano->descricao }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Admin --}}
            <section class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-lg text-slate-800 mb-1">Seus dados de acesso</h2>
                <p class="text-xs text-slate-500 mb-5">Será o admin principal do painel.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Seu nome *</label>
                        <input type="text" name="admin_name" required maxlength="255"
                               value="{{ old('admin_name') }}"
                               placeholder="João da Silva"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">E-mail (login) *</label>
                        <input type="email" name="admin_email" required maxlength="255"
                               value="{{ old('admin_email') }}"
                               placeholder="seu@email.com"
                               autocomplete="email"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Senha *</label>
                        <input type="password" name="admin_password" required minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-[11px] text-slate-500 mt-1">Mínimo 8 caracteres.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirme a senha *</label>
                        <input type="password" name="admin_password_confirmation" required minlength="8"
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
            </section>

            {{-- Termos + captcha --}}
            <section class="bg-white rounded-2xl shadow-sm p-6">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="aceita_termos" value="1" required
                           class="mt-1 w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">
                        Li e concordo com os
                        @php $termos = \App\Models\DocumentoLegal::porSlug('termos-de-uso'); @endphp
                        @if ($termos)
                            <a href="{{ url('/'.$termos->slug) }}" target="_blank" class="text-indigo-600 hover:underline font-medium">Termos de Uso</a>
                        @else
                            <strong>Termos de Uso</strong>
                        @endif
                        e a
                        @php $priv = \App\Models\DocumentoLegal::porSlug('politica-privacidade'); @endphp
                        @if ($priv)
                            <a href="{{ url('/'.$priv->slug) }}" target="_blank" class="text-indigo-600 hover:underline font-medium">Política de Privacidade</a>
                        @else
                            <strong>Política de Privacidade</strong>
                        @endif.
                    </span>
                </label>

                @if ($_captcha->isEnabled())
                    <div class="cf-turnstile mt-4" data-sitekey="{{ $_captcha->siteKey() }}"></div>
                @endif

                <button type="submit"
                        class="mt-6 w-full text-white py-3.5 rounded-xl font-bold text-lg transition hover:opacity-95 shadow-lg"
                        style="background:linear-gradient(135deg,{{ $cor1 }},{{ $cor2 }})">
                    Começar teste grátis de {{ $trial_dias }} dias
                    <i class="ri-arrow-right-line ml-1"></i>
                </button>

                <p class="text-center text-xs text-slate-500 mt-4">
                    Já tem conta?
                    <a href="{{ route('admin.login') }}" class="text-indigo-600 hover:underline font-medium">Fazer login</a>
                </p>
            </section>
        </form>
    </div>
</body>
</html>
