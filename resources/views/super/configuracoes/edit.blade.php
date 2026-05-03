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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Logo</label>
                @if ($config->logo)
                    <div class="flex items-center gap-3 mb-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <img src="{{ $config->logoUrl() }}" alt="Logo atual" class="h-12 w-12 object-contain rounded bg-white p-1">
                        <span class="text-xs text-slate-500 flex-1 break-all">{{ $config->logo }}</span>
                        <label class="text-xs text-rose-600 cursor-pointer">
                            <input type="checkbox" name="remover_logo" value="1" class="mr-1"> Remover
                        </label>
                    </div>
                @endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                <p class="text-xs text-slate-500 mt-1">PNG, JPG, SVG ou WEBP. Máx 1 MB.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Favicon</label>
                @if ($config->favicon)
                    <div class="flex items-center gap-3 mb-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <img src="{{ $config->faviconUrl() }}" alt="Favicon atual" class="h-8 w-8 object-contain rounded bg-white p-0.5">
                        <span class="text-xs text-slate-500 flex-1 break-all">{{ $config->favicon }}</span>
                        <label class="text-xs text-rose-600 cursor-pointer">
                            <input type="checkbox" name="remover_favicon" value="1" class="mr-1"> Remover
                        </label>
                    </div>
                @endif
                <input type="file" name="favicon" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                <p class="text-xs text-slate-500 mt-1">Idealmente 32×32 ou 64×64 px. Máx 256 KB.</p>
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
@endsection
