@extends('layouts.super')
@section('title', 'Configurações do sistema')
@section('content')
<form method="POST" action="{{ route('super.configuracoes.update') }}" enctype="multipart/form-data" class="space-y-4 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Identidade</h2>
        <p class="text-xs text-slate-500 mb-5">Como o sistema se apresenta — nome, marca, slogan, cores.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nome do sistema *</label>
                <input type="text" name="nome_sistema" value="{{ old('nome_sistema', $config->nome_sistema) }}" required maxlength="60"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Aparece no título da aba, login, rodapé.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Slogan / tagline</label>
                <input type="text" name="slogan" value="{{ old('slogan', $config->slogan) }}" maxlength="120"
                       placeholder="ex: Fidelização que aproxima"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cor primária *</label>
                <div class="flex gap-2 items-center">
                    <input type="color" name="cor_primaria" value="{{ old('cor_primaria', $config->cor_primaria) }}" required
                           class="h-10 w-16 rounded border border-slate-300 cursor-pointer">
                    <input type="text" value="{{ old('cor_primaria', $config->cor_primaria) }}" readonly
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono"
                           x-data x-init="$el.previousElementSibling.addEventListener('input', e => $el.value = e.target.value)">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cor secundária *</label>
                <div class="flex gap-2 items-center">
                    <input type="color" name="cor_secundaria" value="{{ old('cor_secundaria', $config->cor_secundaria) }}" required
                           class="h-10 w-16 rounded border border-slate-300 cursor-pointer">
                    <input type="text" value="{{ old('cor_secundaria', $config->cor_secundaria) }}" readonly
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono"
                           x-data x-init="$el.previousElementSibling.addEventListener('input', e => $el.value = e.target.value)">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-6">
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200"
                 x-data="iconePreview({
                    src: '{{ $config->logoUrl() ?? '' }}',
                    bg: '{{ old('logo_bg_color', $config->logo_bg_color ?? '#000000') }}',
                    scale: {{ old('logo_scale', $config->logo_scale ?? 100) }},
                 })">
                <h3 class="font-semibold mb-1 text-slate-700 flex items-center gap-2 text-sm">
                    <i class="ri-image-fill text-indigo-600"></i> Ícone do app
                </h3>
                <p class="text-[11px] text-slate-500 mb-3">PNG transparente quadrado. Máx 8 MB.</p>

                <div class="flex gap-4">
                    <div class="text-center shrink-0">
                        <div class="w-24 h-24 rounded-xl shadow-sm flex items-center justify-center overflow-hidden"
                             :style="`background:${bg}`">
                            <template x-if="src">
                                <img :src="src" :style="`width:${scale}%;height:${scale}%;object-fit:contain`" alt="">
                            </template>
                            <template x-if="!src">
                                <i class="ri-image-line text-3xl text-white/60"></i>
                            </template>
                        </div>
                        @if ($config->logo)
                            <label class="inline-flex items-center gap-1 text-[10px] text-rose-600 cursor-pointer mt-1">
                                <input type="checkbox" name="remover_logo" value="1"> Remover
                            </label>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0 space-y-2">
                        <label class="block border-2 border-dashed border-slate-300 rounded-lg p-3 text-center cursor-pointer hover:border-rose-400 hover:bg-white transition">
                            <i class="ri-upload-cloud-2-line text-xl text-slate-400"></i>
                            <p class="text-xs font-semibold text-slate-700">Enviar logo (PNG)</p>
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                   @change="previewArquivo($event)" class="hidden">
                        </label>

                        <div class="flex gap-2 items-center">
                            <input type="color" :value="bg" @input="bg = $event.target.value" class="w-9 h-8 border border-slate-300 rounded cursor-pointer shrink-0">
                            <input type="text" name="logo_bg_color" x-model="bg" maxlength="7"
                                   class="flex-1 px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-mono">
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-500 flex items-center justify-between">
                                <span>Tamanho do PNG</span>
                                <span class="text-rose-600 font-semibold" x-text="`${scale}%`"></span>
                            </label>
                            <input type="range" name="logo_scale" min="30" max="150" step="5" x-model.number="scale"
                                   class="w-full accent-rose-600">
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200"
                 x-data="iconePreview({
                    src: '{{ $config->faviconUrl() ?? '' }}',
                    bg: '{{ old('favicon_bg_color', $config->favicon_bg_color ?? '#000000') }}',
                    scale: {{ old('favicon_scale', $config->favicon_scale ?? 100) }},
                 })">
                <h3 class="font-semibold mb-1 text-slate-700 flex items-center gap-2 text-sm">
                    <i class="ri-shape-2-line text-indigo-600"></i> Favicon (aba do navegador)
                </h3>
                <p class="text-[11px] text-slate-500 mb-3">64×64 px PNG transparente. Máx 1 MB.</p>

                <div class="flex gap-4">
                    <div class="text-center shrink-0">
                        <div class="w-24 h-24 rounded-xl shadow-sm flex items-center justify-center overflow-hidden"
                             :style="`background:${bg}`">
                            <template x-if="src">
                                <img :src="src" :style="`width:${scale}%;height:${scale}%;object-fit:contain`" alt="">
                            </template>
                            <template x-if="!src">
                                <i class="ri-shape-2-line text-3xl text-white/60"></i>
                            </template>
                        </div>
                        @if ($config->favicon)
                            <label class="inline-flex items-center gap-1 text-[10px] text-rose-600 cursor-pointer mt-1">
                                <input type="checkbox" name="remover_favicon" value="1"> Remover
                            </label>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0 space-y-2">
                        <label class="block border-2 border-dashed border-slate-300 rounded-lg p-3 text-center cursor-pointer hover:border-rose-400 hover:bg-white transition">
                            <i class="ri-upload-cloud-2-line text-xl text-slate-400"></i>
                            <p class="text-xs font-semibold text-slate-700">Enviar favicon</p>
                            <input type="file" name="favicon" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon"
                                   @change="previewArquivo($event)" class="hidden">
                        </label>

                        <div class="flex gap-2 items-center">
                            <input type="color" :value="bg" @input="bg = $event.target.value" class="w-9 h-8 border border-slate-300 rounded cursor-pointer shrink-0">
                            <input type="text" name="favicon_bg_color" x-model="bg" maxlength="7"
                                   class="flex-1 px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-mono">
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-500 flex items-center justify-between">
                                <span>Tamanho do ícone</span>
                                <span class="text-rose-600 font-semibold" x-text="`${scale}%`"></span>
                            </label>
                            <input type="range" name="favicon_scale" min="30" max="150" step="5" x-model.number="scale"
                                   class="w-full accent-rose-600">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Contato e suporte</h2>
        <p class="text-xs text-slate-500 mb-5">Aparece nas páginas legais e (futuramente) em e-mails do sistema.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">E-mail de suporte</label>
                <input type="email" name="email_suporte" value="{{ old('email_suporte', $config->email_suporte) }}" maxlength="120"
                       placeholder="suporte@seudominio.com"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Site (URL pública)</label>
                <input type="url" name="site_url" value="{{ old('site_url', $config->site_url) }}" maxlength="120"
                       placeholder="https://satisfy.com.br"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Telefone</label>
                <input type="text" name="telefone_suporte" value="{{ old('telefone_suporte', $config->telefone_suporte) }}" maxlength="30"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp</label>
                <input type="text" name="whatsapp_suporte" value="{{ old('whatsapp_suporte', $config->whatsapp_suporte) }}" maxlength="30"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Dados da empresa (LGPD)</h2>
        <p class="text-xs text-slate-500 mb-5">Aparecem nas páginas legais e em comunicações oficiais.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Razão social</label>
                <input type="text" name="razao_social" value="{{ old('razao_social', $config->razao_social) }}" maxlength="120"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">CNPJ</label>
                <input type="text" name="cnpj" value="{{ old('cnpj', $config->cnpj) }}" maxlength="20"
                       placeholder="00.000.000/0000-00"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                <input type="text" name="endereco" value="{{ old('endereco', $config->endereco) }}" maxlength="255"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-semibold text-slate-800">Pagamentos PIX</h2>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="pix_ativo" value="1" {{ old('pix_ativo', $config->pix_ativo) ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-slate-200 peer-checked:bg-emerald-500 rounded-full relative transition">
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                </div>
                <span class="text-sm font-medium">{{ $config->pix_ativo ? 'Ativo' : 'Desativado' }}</span>
            </label>
        </div>
        <p class="text-xs text-slate-500 mb-5">Gateway pra cobrar as assinaturas das empresas via PIX. Sem ativar, fica em modo mock (QR fake só pra dev).</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Gateway</label>
                <select name="pix_provider" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg">
                    <option value="mock" @selected(old('pix_provider', $config->pix_provider) === 'mock')>Mock (desenvolvimento)</option>
                    <option value="asaas" @selected(old('pix_provider', $config->pix_provider) === 'asaas')>Asaas</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ambiente</label>
                <select name="pix_ambiente" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg">
                    <option value="sandbox" @selected(old('pix_ambiente', $config->pix_ambiente) === 'sandbox')>Sandbox (testes)</option>
                    <option value="producao" @selected(old('pix_ambiente', $config->pix_ambiente) === 'producao')>Produção</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">API Key</label>
                <input type="password" name="pix_api_key" autocomplete="off"
                       placeholder="{{ $config->pix_api_key ? '••••••••• (salva — só sobrescreve se preencher)' : 'Cole aqui sua chave do Asaas' }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg font-mono text-sm">
                <p class="text-xs text-slate-500 mt-1">
                    A chave fica criptografada no banco. Pegue em <a href="https://www.asaas.com/" target="_blank" class="text-rose-600 hover:underline">asaas.com</a> → Configurações → Integrações.
                </p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Token de autenticação do webhook
                    <span class="text-rose-600">*</span>
                </label>
                <input type="password" name="asaas_webhook_token" autocomplete="off" minlength="16" maxlength="200"
                       placeholder="{{ $config->asaas_webhook_token ? '••••••••• (salvo — só sobrescreve se preencher)' : 'Mínimo 16 caracteres' }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg font-mono text-sm">
                <p class="text-xs text-slate-500 mt-1">
                    Segredo compartilhado com o Asaas: nosso webhook só aceita requisições que tragam esse valor no header
                    <code class="bg-slate-100 px-1 rounded">asaas-access-token</code>. Sem ele configurado, todo webhook é rejeitado (401).
                    Cadastre o mesmo valor no painel Asaas → Integrações → Webhooks → Token de autenticação.
                </p>
                {{-- URL sempre visível pro super copiar e colar no painel Asaas
                     (antes só aparecia depois de salvar o token — ovo/galinha
                     pq o user precisa da URL pra cadastrar no Asaas ANTES de
                     gerar o token). Estrutura HTML idêntica à original que
                     só era exibida sob @if asaas_webhook_token. --}}
                <div class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg">
                    <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider mb-1.5">
                        <i class="ri-link"></i> URL do webhook Asaas
                    </p>
                    <code class="block text-xs font-mono bg-white border border-rose-200 px-3 py-2 rounded break-all">{{ url('/webhook/pagamento/asaas') }}</code>
                    <p class="text-[11px] text-rose-700 mt-2">
                        Configure no Asaas como webhook dos eventos <code>PAYMENT_RECEIVED</code> e <code>PAYMENT_CONFIRMED</code> com o token acima.
                    </p>
                </div>
            </div>
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Plano padrão pra novas empresas</label>
                    <select name="plano_padrao_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <option value="">— Não criar assinatura automaticamente —</option>
                        @foreach ($planos as $p)
                            <option value="{{ $p->id }}" @selected(old('plano_padrao_id', $config->plano_padrao_id) == $p->id)>
                                {{ $p->nome }} (R$ {{ number_format($p->preco_mensal, 2, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trial (dias)</label>
                    <input type="number" name="trial_dias_padrao" value="{{ old('trial_dias_padrao', $config->trial_dias_padrao ?? 7) }}" min="0" max="90"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    <p class="text-[11px] text-slate-500 mt-1">Dias de teste gratuito antes da primeira cobrança. 0 = sem trial.</p>
                </div>

                <div class="sm:col-span-2 pt-3 border-t border-slate-200">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="cadastro_publico_ativo" value="1"
                               @checked(old('cadastro_publico_ativo', $config->cadastro_publico_ativo ?? true))
                               class="mt-0.5 w-5 h-5 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>
                            <span class="block text-sm font-medium text-slate-800">Cadastro público de empresa</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">
                                Quando ligado, lojistas se cadastram sozinhos em
                                <code class="text-rose-600">{{ url('/cadastro') }}</code> escolhendo um plano ativo
                                e ganham o trial acima automaticamente. Desligue se quiser triar empresas manualmente
                                (cadastro só via super admin) — a página fica acessível mas mostra "indisponível".
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Avisos antes do vencimento (dias)</label>
                    <input type="text" name="cobranca_avisos_antes" value="{{ old('cobranca_avisos_antes', $config->cobranca_avisos_antes ?? '3,1,0') }}"
                           placeholder="3,1,0" maxlength="60"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">CSV de dias antes do vencimento. Ex: <code>3,1,0</code> = avisa 3 dias antes, 1 dia antes e no dia. Vazio = não avisa.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Reminders após vencer (dias)</label>
                    <input type="text" name="cobranca_avisos_depois" value="{{ old('cobranca_avisos_depois', $config->cobranca_avisos_depois ?? '1,7,15,30') }}"
                           placeholder="1,7,15,30" maxlength="60"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">CSV de dias após o vencimento. Ex: <code>1,7,15,30</code> = reminder 1, 7, 15 e 30 dias após vencer.</p>
                </div>
            </div>

            @if ($config->pix_webhook_token)
                <div class="md:col-span-2 p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                        <i class="ri-link"></i> URL do webhook (configure no painel do gateway)
                    </p>
                    <code class="block text-xs font-mono bg-white border border-slate-200 px-3 py-2 rounded break-all">{{ url('/webhook/pix') }}</code>
                    <p class="text-[11px] text-slate-500 mt-2">
                        <strong>Recomendado:</strong> envie o token no header <code>X-Pix-Webhook-Token</code> (token aparece em logs se ficar na URL).<br>
                        Token atual: <code class="text-[11px]">{{ str_repeat('•', max(0, strlen($config->pix_webhook_token) - 6)) . substr($config->pix_webhook_token, -6) }}</code>
                        <a href="javascript:navigator.clipboard.writeText({{ json_encode($config->pix_webhook_token) }})" class="text-indigo-600 hover:underline">copiar</a>.<br>
                        Se o gateway só aceitar token na URL: <code class="text-[11px]">{{ url('/webhook/pix/SEU_TOKEN') }}</code>
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Captcha (anti-robô)</h2>
        <p class="text-xs text-slate-500 mb-5">
            Adiciona verificação anti-robô no login admin, no cadastro/login do cliente (PWA),
            na solicitação de OTP e na recuperação de senha. Recomendado pra fechar brute force
            distribuído com botnet — o throttle por IP sozinho não pega.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Provider</label>
                <select name="captcha_provider"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    <option value="disabled" @selected(old('captcha_provider', $config->captcha_provider ?? 'disabled') === 'disabled')>Desligado</option>
                    <option value="turnstile" @selected(old('captcha_provider', $config->captcha_provider ?? 'disabled') === 'turnstile')>Cloudflare Turnstile</option>
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Turnstile é gratuito e sem fricção visual.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Site Key</label>
                <input type="text" name="captcha_site_key" autocomplete="off"
                       value="{{ old('captcha_site_key', $config->captcha_site_key ?? '') }}"
                       placeholder="0x4AAAAAAA..."
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                <p class="text-[11px] text-slate-500 mt-1">Chave pública (vai no frontend).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Secret Key</label>
                <input type="password" name="captcha_secret_key" autocomplete="off"
                       placeholder="{{ $config->captcha_secret_key ? '••••••••• (salva — só sobrescreve se preencher)' : '0x4AAAAAAA...' }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                <p class="text-[11px] text-slate-500 mt-1">Cifrada no banco. Use a do mesmo widget.</p>
            </div>
        </div>

        <div class="mt-4 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 leading-relaxed">
            <p class="font-semibold text-slate-700 mb-1.5">
                <i class="ri-information-line"></i> Como obter as chaves:
            </p>
            <ol class="list-decimal list-inside space-y-1 ml-1">
                <li>Acesse <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" class="text-rose-600 hover:underline">dash.cloudflare.com → Turnstile → Add site</a></li>
                <li>Domínio: seu domínio de produção (ex: <code>satisfy.com.br</code>)</li>
                <li>Widget Mode: <strong>Managed</strong> (recomendado, sem fricção pro usuário legítimo)</li>
                <li>Copie <strong>Site Key</strong> e <strong>Secret Key</strong> nos campos acima</li>
                <li>Salve. Backend valida via <em>fail-closed</em> — se a Cloudflare estiver fora, o request é rejeitado.</li>
            </ol>
            <p class="mt-2">
                Quando ligado, o widget aparece automaticamente no <code>/admin/login</code>.
                Para as APIs (cliente PWA), o frontend precisa enviar o token via campo
                <code>cf-turnstile-response</code> ou header <code>X-Captcha-Token</code>.
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Tarefas agendadas (cron)</h2>
        <p class="text-xs text-slate-500 mb-5">Horários em que o sistema roda tarefas automáticas. Os horários abaixo só são respeitados se o cron <code class="bg-slate-100 px-1 rounded">php artisan schedule:run</code> estiver rodando a cada minuto no servidor.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Horário das automações WhatsApp</label>
                <input type="time" name="horario_automacoes"
                       value="{{ old('horario_automacoes', $config->horario_automacoes ?? '09:00') }}" required
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">
                    Mensagens enviadas automaticamente em momentos chave: cadastro, aniversário, pós-compra, clientes inativos.
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Horário da liberação de cashback</label>
                <input type="time" name="horario_cashback"
                       value="{{ old('horario_cashback', $config->horario_cashback ?? '03:00') }}" required
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">
                    Cashbacks pendentes que passaram do prazo viram disponíveis pro cliente. Execute em horário de baixo movimento.
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1 flex items-center gap-2">
            <i class="ri-shield-check-line text-rose-600"></i> Antifraude e limites
        </h2>
        <p class="text-xs text-slate-500 mb-5">
            Aplicado globalmente a todas as empresas. Valores muito altos facilitam ataques, muito baixos atrapalham clientes legítimos.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Login/Registro/OTP por minuto *</label>
                <input type="number" name="rate_limit_auth" min="1" max="1000" required
                       value="{{ old('rate_limit_auth', $config->rate_limit_auth ?: 10) }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Tentativas por IP por minuto. Padrão: 10.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Webhook PDV por minuto *</label>
                <input type="number" name="rate_limit_pdv" min="1" max="5000" required
                       value="{{ old('rate_limit_pdv', $config->rate_limit_pdv ?: 60) }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Lançamentos pelo PDV externo por IP por minuto. Padrão: 60.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">OTPs por telefone (15 min) *</label>
                <input type="number" name="otp_max_por_telefone" min="1" max="50" required
                       value="{{ old('otp_max_por_telefone', $config->otp_max_por_telefone ?: 3) }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Códigos OTP por telefone em 15 min. Padrão: 3.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tentativas por código OTP *</label>
                <input type="number" name="otp_max_tentativas" min="1" max="50" required
                       value="{{ old('otp_max_tentativas', $config->otp_max_tentativas ?: 5) }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Erros antes do código ser invalidado. Padrão: 5.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Resgates por cliente em 24h *</label>
                <input type="number" name="max_resgates_24h" min="1" max="100" required
                       value="{{ old('max_resgates_24h', $config->max_resgates_24h ?: 3) }}"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Quantos prêmios o mesmo cliente pode resgatar por dia. Padrão: 3.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-1">Rodapé público</h2>
        <p class="text-xs text-slate-500 mb-5">HTML pequeno que aparece no rodapé das páginas públicas (login, documentos legais).</p>
        <textarea name="rodape_html" rows="3" maxlength="1000"
                  placeholder="© {{ date('Y') }} Sua Empresa. Todos os direitos reservados."
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none font-mono text-xs">{{ old('rodape_html', $config->rodape_html) }}</textarea>
    </div>

    <div class="flex justify-end gap-2">
        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded-lg font-medium">
            <i class="ri-save-line"></i> Salvar configurações
        </button>
    </div>
</form>

<script>
function iconePreview(initial) {
    return {
        src: initial.src,
        bg: initial.bg,
        scale: initial.scale,
        previewArquivo(e) {
            const f = e.target.files?.[0];
            if (!f) return;
            const reader = new FileReader();
            reader.onload = () => { this.src = reader.result; };
            reader.readAsDataURL(f);
        },
    }
}
</script>
@endsection
