@extends('layouts.admin')
@section('title', 'WhatsApp — Integração')
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-4 flex items-center gap-2">
            <i class="ri-whatsapp-line text-emerald-600"></i> Configuração do provedor
        </h2>

        <form method="POST" action="{{ route('admin.whatsapp.update') }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="text-sm font-medium">Provedor</label>
                <select name="whatsapp_provider" x-data x-on:change="window.location.hash = $event.target.value"
                        class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="mock" @selected($empresa->whatsapp_provider === 'mock')>🧪 Mock (apenas logs — modo dev)</option>
                    <option value="evolution" @selected($empresa->whatsapp_provider === 'evolution')>Evolution API (open-source)</option>
                    <option value="zapi" @selected($empresa->whatsapp_provider === 'zapi')>Z-API (z-api.io)</option>
                    <option value="meta_cloud" @selected($empresa->whatsapp_provider === 'meta_cloud')>WhatsApp Cloud API (Meta oficial)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium">API URL</label>
                    <input type="url" name="whatsapp_api_url" value="{{ old('whatsapp_api_url', $empresa->whatsapp_api_url) }}"
                           placeholder="https://..."
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium">API Token</label>
                    <input type="text" name="whatsapp_api_token" value="{{ old('whatsapp_api_token', $empresa->whatsapp_api_token) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium">Instance ID (Evolution / Z-API)</label>
                    <input type="text" name="whatsapp_instance" value="{{ old('whatsapp_instance', $empresa->whatsapp_instance) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium">Phone Number ID (Meta Cloud)</label>
                    <input type="text" name="whatsapp_phone_id" value="{{ old('whatsapp_phone_id', $empresa->whatsapp_phone_id) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                </div>
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
