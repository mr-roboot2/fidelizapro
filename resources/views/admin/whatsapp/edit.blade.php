@extends('layouts.admin')
@section('title', 'WhatsApp — Integração')
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4 flex items-center gap-2">
            <i class="ri-whatsapp-line text-emerald-600"></i> Configuração do provedor
        </h2>

        <form method="POST" action="{{ route('admin.whatsapp.update') }}" class="space-y-4"
              x-data="{ provedor: '{{ old('whatsapp_provider', $empresa->whatsapp_provider) }}' }">
            @csrf @method('PUT')

            <div>
                <label class="text-sm font-medium">Provedor</label>
                <select name="whatsapp_provider" x-model="provedor"
                        class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="mock">🧪 Mock (apenas logs — modo dev)</option>
                    <option value="evolution">Evolution API (open-source)</option>
                    <option value="zapi">Z-API (z-api.io)</option>
                    <option value="meta_cloud">WhatsApp Cloud API (Meta oficial)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div x-show="provedor === 'evolution' || provedor === 'zapi'">
                    <label class="text-sm font-medium">API URL</label>
                    <input type="url" name="whatsapp_api_url" value="{{ old('whatsapp_api_url', $empresa->whatsapp_api_url) }}"
                           placeholder="https://..."
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'evolution'">URL da sua instância Evolution (ex: <code>https://evolution.seudominio.com.br</code>)</p>
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'zapi'"><code>https://api.z-api.io</code></p>
                </div>

                <div x-show="provedor !== 'mock'">
                    <label class="text-sm font-medium">API Token</label>
                    <input type="text" name="whatsapp_api_token" value="{{ old('whatsapp_api_token', $empresa->whatsapp_api_token) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'meta_cloud'">System User Token (Bearer) gerado no Meta Business Manager</p>
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'evolution'">apikey gerada na criação da instância</p>
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'zapi'">Client-Token do painel Z-API</p>
                </div>

                <div x-show="provedor === 'evolution' || provedor === 'zapi'">
                    <label class="text-sm font-medium">Instance ID</label>
                    <input type="text" name="whatsapp_instance" value="{{ old('whatsapp_instance', $empresa->whatsapp_instance) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'evolution'">Nome da instância criada no Evolution</p>
                    <p class="text-[11px] text-slate-500 mt-1" x-show="provedor === 'zapi'">ID + token da sua instância Z-API</p>
                </div>

                <div x-show="provedor === 'meta_cloud'">
                    <label class="text-sm font-medium">Phone Number ID</label>
                    <input type="text" name="whatsapp_phone_id" value="{{ old('whatsapp_phone_id', $empresa->whatsapp_phone_id) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                    <p class="text-[11px] text-slate-500 mt-1">ID do telefone WhatsApp (no painel Meta → WhatsApp → API setup)</p>
                </div>

                <div x-show="provedor === 'meta_cloud'">
                    <label class="text-sm font-medium">WhatsApp Business Account ID</label>
                    <input type="text" name="whatsapp_waba_id" value="{{ old('whatsapp_waba_id', $empresa->whatsapp_waba_id) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                    <p class="text-[11px] text-slate-500 mt-1">WABA ID — necessário pra listar templates. No painel Meta → WhatsApp → API setup, logo abaixo do Phone Number ID.</p>
                </div>
            </div>

            <div x-show="provedor === 'meta_cloud'" class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-800">
                <i class="ri-information-line"></i> Para Meta Cloud, o <strong>API URL</strong> não é necessário — o sistema usa <code>graph.facebook.com</code> automaticamente.
            </div>

            <label class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="whatsapp_ativo" value="1" {{ old('whatsapp_ativo', $empresa->whatsapp_ativo) ? 'checked' : '' }} class="rounded">
                <span class="text-sm font-medium">Ativar envios reais (desativado = usa mock)</span>
            </label>

            <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Salvar configurações</button>
        </form>

        <hr class="my-6">

        <h3 class="font-semibold mb-3">Testar conexão</h3>
        <form method="POST" action="{{ route('admin.whatsapp.testar') }}" class="flex gap-2">
            @csrf
            <input type="text" name="telefone_destino" required placeholder="Seu número (ex: 5511999999999)"
                   class="flex-1 px-3 py-2 border border-slate-300 rounded-lg">
            <button class="px-5 py-2 bg-emerald-600 text-white rounded-lg">
                <i class="ri-send-plane-line"></i> Enviar teste
            </button>
        </form>
        <p class="text-xs text-slate-500 mt-2">Em modo Mock, a "mensagem" cai em <code>storage/logs/laravel.log</code>.</p>

        @if ($empresa->whatsapp_provider === 'meta_cloud')
            <hr class="my-6">

            <h3 class="font-semibold mb-2 flex items-center gap-2">
                <i class="ri-link-m text-indigo-600"></i> Webhook (configurar no painel Meta)
            </h3>
            <p class="text-xs text-slate-600 mb-3">
                Cole estes valores em <strong>WhatsApp → Configuração → Webhooks</strong> no Meta Developer Console.
            </p>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider">URL de callback</label>
                    <div class="flex gap-2 mt-1">
                        <input type="text" readonly id="webhook-url"
                               value="{{ url('/webhook/whatsapp/meta/'.$empresa->slug) }}"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono text-xs">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url').value); this.innerText='Copiado!'; setTimeout(()=>this.innerText='Copiar', 1500)"
                                class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700">Copiar</button>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Verificar token</label>
                    <div class="flex gap-2 mt-1">
                        <input type="text" readonly id="webhook-token"
                               value="{{ $empresa->whatsapp_webhook_verify_token }}"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono text-xs">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-token').value); this.innerText='Copiado!'; setTimeout(()=>this.innerText='Copiar', 1500)"
                                class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700">Copiar</button>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.whatsapp.regenerar-webhook-token') }}" class="mt-3"
                  onsubmit="return confirm('Gerar um token novo? Você precisará atualizar no painel da Meta também.');">
                @csrf
                <button type="submit" class="text-xs text-rose-600 hover:underline">
                    <i class="ri-refresh-line"></i> Gerar novo token
                </button>
            </form>

            <div class="mt-4 bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs text-amber-800">
                <p class="font-semibold mb-1"><i class="ri-information-line"></i> Depois de salvar lá no Meta:</p>
                <ol class="list-decimal list-inside space-y-0.5">
                    <li>Selecione <strong>WhatsApp Business Account</strong> no produto</li>
                    <li>Cole a URL e o token acima → <em>Verificar e salvar</em></li>
                    <li>Em <strong>Inscrever campos</strong>, marque pelo menos <code>messages</code> (mensagens entrantes)</li>
                </ol>
            </div>
        @endif
    </div>

    <!-- Documentação -->
    <div class="bg-slate-50 rounded-xl p-5 text-sm space-y-4">
        <h3 class="font-semibold text-slate-700">📚 Provedores</h3>

        <div>
            <p class="font-semibold text-slate-700">Evolution API</p>
            <p class="text-xs text-slate-600 mt-1">Open-source, self-hosted. Mais econômico para volumes médios. URL = sua instância. Instance = nome da instância criada.</p>
            <a href="https://doc.evolution-api.com/" target="_blank" class="text-xs text-indigo-600">Docs →</a>
        </div>

        <div>
            <p class="font-semibold text-slate-700">Z-API</p>
            <p class="text-xs text-slate-600 mt-1">Provedor brasileiro pago (planos a partir de ~R$60/mês). Suporte em PT-BR. URL geralmente <code>https://api.z-api.io</code>.</p>
            <a href="https://developer.z-api.io/" target="_blank" class="text-xs text-indigo-600">Docs →</a>
        </div>

        <div>
            <p class="font-semibold text-slate-700">Meta Cloud (oficial)</p>
            <p class="text-xs text-slate-600 mt-1">Oficial WhatsApp Business. Cobra por conversa. Precisa cadastro no Business Manager + conta verificada.</p>
            <a href="https://developers.facebook.com/docs/whatsapp/cloud-api" target="_blank" class="text-xs text-indigo-600">Docs →</a>
        </div>
    </div>
</div>
@endsection
