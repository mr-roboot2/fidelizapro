@extends('layouts.super')
@section('title', 'WhatsApp — Integração global')
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-1 flex items-center gap-2">
            <i class="ri-whatsapp-line text-emerald-600"></i> Configuração do provedor
        </h2>
        <p class="text-xs text-slate-500 mb-5">Esta configuração é <strong>global</strong> — todas as empresas do SaaS usam a mesma integração WhatsApp.</p>

        <form method="POST" action="{{ route('super.whatsapp.update') }}" class="space-y-4"
              x-data="{ provedor: '{{ old('whatsapp_provider', $config->whatsapp_provider) }}' }">
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
                    <input type="url" name="whatsapp_api_url" value="{{ old('whatsapp_api_url', $config->whatsapp_api_url) }}"
                           placeholder="https://..."
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>

                <div x-show="provedor !== 'mock'">
                    <label class="text-sm font-medium">API Token</label>
                    <input type="text" name="whatsapp_api_token" value="{{ old('whatsapp_api_token', $config->whatsapp_api_token) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>

                <div x-show="provedor === 'evolution' || provedor === 'zapi'">
                    <label class="text-sm font-medium">Instance ID</label>
                    <input type="text" name="whatsapp_instance" value="{{ old('whatsapp_instance', $config->whatsapp_instance) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>

                <div x-show="provedor === 'meta_cloud'">
                    <label class="text-sm font-medium">Phone Number ID</label>
                    <input type="text" name="whatsapp_phone_id" value="{{ old('whatsapp_phone_id', $config->whatsapp_phone_id) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>

                <div x-show="provedor === 'meta_cloud'">
                    <label class="text-sm font-medium">WhatsApp Business Account ID</label>
                    <input type="text" name="whatsapp_waba_id" value="{{ old('whatsapp_waba_id', $config->whatsapp_waba_id) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>
            </div>

            <label class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="whatsapp_ativo" value="1" {{ old('whatsapp_ativo', $config->whatsapp_ativo) ? 'checked' : '' }} class="rounded">
                <span class="text-sm font-medium">Ativar envios reais (desativado = usa mock)</span>
            </label>

            <button class="px-5 py-2 bg-rose-600 text-white rounded-lg">Salvar configurações</button>
        </form>

        <hr class="my-6">

        <h3 class="font-semibold mb-3">Testar conexão</h3>
        <form method="POST" action="{{ route('super.whatsapp.testar') }}" class="flex gap-2">
            @csrf
            <input type="text" name="telefone_destino" required placeholder="Seu número (ex: 5511999999999)"
                   class="flex-1 px-3 py-2 border border-slate-300 rounded-lg">
            <button class="px-5 py-2 bg-emerald-600 text-white rounded-lg">
                <i class="ri-send-plane-line"></i> Enviar teste
            </button>
        </form>

        @if ($config->whatsapp_provider === 'meta_cloud')
            <hr class="my-6">

            <h3 class="font-semibold mb-2 flex items-center gap-2">
                <i class="ri-link-m text-indigo-600"></i> Webhook (configurar no painel Meta)
            </h3>
            <p class="text-xs text-slate-600 mb-3">Cole estes valores em <strong>WhatsApp → Configuração → Webhooks</strong> no Meta Developer Console.</p>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider">URL de callback</label>
                    <div class="flex gap-2 mt-1">
                        <input type="text" readonly id="webhook-url"
                               value="{{ url('/webhook/whatsapp/meta') }}"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono text-xs">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url').value); this.innerText='Copiado!'; setTimeout(()=>this.innerText='Copiar', 1500)"
                                class="px-3 py-2 bg-rose-600 text-white rounded-lg text-xs hover:bg-rose-700">Copiar</button>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Verificar token</label>
                    <div class="flex gap-2 mt-1">
                        <input type="text" readonly id="webhook-token"
                               value="{{ $config->whatsapp_webhook_verify_token }}"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono text-xs">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-token').value); this.innerText='Copiado!'; setTimeout(()=>this.innerText='Copiar', 1500)"
                                class="px-3 py-2 bg-rose-600 text-white rounded-lg text-xs hover:bg-rose-700">Copiar</button>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('super.whatsapp.regenerar-webhook-token') }}" class="mt-3"
                  onsubmit="return confirm('Gerar um token novo? Você precisará atualizar no painel da Meta também.');">
                @csrf
                <button type="submit" class="text-xs text-rose-600 hover:underline">
                    <i class="ri-refresh-line"></i> Gerar novo token
                </button>
            </form>
        @endif
    </div>

    <div class="bg-slate-50 rounded-xl p-5 text-sm space-y-4">
        <h3 class="font-semibold text-slate-700">📚 Como funciona</h3>
        <p class="text-xs text-slate-600">
            A integração é única pra todo o SaaS — uma WABA, um número, todas as empresas usam.
            Templates aprovados também são compartilhados.
        </p>
        <p class="text-xs text-slate-600">
            Pro nome da empresa aparecer na mensagem (ex: boas-vindas), use o template com parâmetro <code>{{ '{{empresa}}' }}</code>.
        </p>
        <a href="{{ route('super.whatsapp-templates.index') }}" class="block text-sm bg-rose-600 hover:bg-rose-700 text-white text-center py-2 rounded-lg">
            <i class="ri-message-3-line"></i> Gerenciar Templates
        </a>
    </div>
</div>
@endsection
