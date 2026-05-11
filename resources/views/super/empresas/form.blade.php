@extends('layouts.super')
@section('title', $empresa->exists ? 'Editar empresa' : 'Nova empresa')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl">
    <form method="POST"
          action="{{ $empresa->exists ? route('super.empresas.update', $empresa) : route('super.empresas.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($empresa->exists) @method('PUT') @endif

        <h3 class="font-semibold mb-4 text-slate-700">Dados da empresa</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome', $empresa->nome) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Slug (URL)</label>
                <input type="text" name="slug" value="{{ old('slug', $empresa->slug) }}"
                       placeholder="gerado automaticamente"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
            </div>
            <div>
                <label class="text-sm font-medium">CNPJ</label>
                <input type="text" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">Telefone</label>
                <input type="text" name="telefone" value="{{ old('telefone', $empresa->telefone) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-sm font-medium">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $empresa->email) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Endereço</label>
                <input type="text" name="endereco" value="{{ old('endereco', $empresa->endereco) }}"
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>

        <div class="mt-6 p-5 bg-slate-50 rounded-xl border border-slate-200"
             x-data="iconePreview({
                src: '{{ $empresa->logo ? asset('storage/'.$empresa->logo) : '' }}',
                bg: '{{ old('logo_bg_color', $empresa->logo_bg_color ?? '#000000') }}',
                scale: {{ old('logo_scale', $empresa->logo_scale ?? 100) }},
             })">
            <h3 class="font-semibold mb-1 text-slate-700 flex items-center gap-2">
                <i class="ri-image-fill text-indigo-600"></i> Ícone da empresa
            </h3>
            <p class="text-xs text-slate-500 mb-4">
                O ícone é composto pela <strong>cor de fundo</strong> + <strong>PNG que você enviar</strong>
                (o PNG fica centralizado por cima). Idealmente use um PNG quadrado com fundo transparente. Máx. 8MB.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-[180px_1fr] gap-5">
                <div class="text-center">
                    <div class="w-40 h-40 rounded-2xl shadow-sm mx-auto flex items-center justify-center overflow-hidden"
                         :style="`background:${bg}`">
                        <template x-if="src">
                            <img :src="src" :style="`width:${scale}%;height:${scale}%;object-fit:contain`" alt="">
                        </template>
                        <template x-if="!src">
                            <i class="ri-image-line text-5xl text-white/60"></i>
                        </template>
                    </div>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider mt-2 font-semibold" x-text="src ? 'PERSONALIZADO' : 'SEM IMAGEM'"></p>
                </div>

                <div class="space-y-4">
                    <label class="block border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 hover:bg-white transition">
                        <i class="ri-upload-cloud-2-line text-3xl text-slate-400 block mb-1"></i>
                        <p class="text-sm font-semibold text-slate-700">Clique ou arraste o logo (PNG)</p>
                        <p class="text-xs text-slate-500 mt-0.5">PNG transparente · máximo 8 MB</p>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"
                               @change="previewArquivo($event)" class="hidden">
                    </label>

                    <div>
                        <label class="text-xs text-slate-600 flex items-center gap-1 mb-1">
                            <i class="ri-palette-line"></i> Cor de fundo
                        </label>
                        <div class="flex gap-2">
                            <input type="color" :value="bg" @input="bg = $event.target.value" class="w-12 h-10 border border-slate-300 rounded cursor-pointer">
                            <input type="text" name="logo_bg_color" x-model="bg" maxlength="7"
                                   class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-slate-600 flex items-center justify-between mb-1">
                            <span class="flex items-center gap-1"><i class="ri-fullscreen-line"></i> Tamanho do PNG</span>
                            <span class="text-indigo-600 font-semibold" x-text="`${scale}%`"></span>
                        </label>
                        <input type="range" name="logo_scale" min="30" max="150" step="5" x-model.number="scale"
                               class="w-full accent-indigo-600">
                    </div>
                </div>
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-4 text-slate-700">Identidade visual</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Cor primária</label>
                <input type="color" name="cor_primaria" value="{{ old('cor_primaria', $empresa->cor_primaria ?? '#6366f1') }}"
                       class="mt-1 w-full h-12 border border-slate-300 rounded-lg cursor-pointer">
            </div>
            <div>
                <label class="text-sm font-medium">Cor secundária</label>
                <input type="color" name="cor_secundaria" value="{{ old('cor_secundaria', $empresa->cor_secundaria ?? '#8b5cf6') }}"
                       class="mt-1 w-full h-12 border border-slate-300 rounded-lg cursor-pointer">
            </div>
        </div>

        <h3 class="font-semibold mt-6 mb-4 text-slate-700">Programa de fidelidade</h3>
        <div x-data="{ modo: '{{ old('modo_fidelidade', $empresa->modo_fidelidade ?? 'ambos') }}' }" class="space-y-4">
            <div>
                <label class="text-sm font-medium mb-2 block">Como a empresa premia o cliente *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach (\App\Models\Empresa::MODOS_FIDELIDADE as $valor => $rotulo)
                        <label :class="modo === '{{ $valor }}' ? 'border-rose-500 bg-rose-50' : 'border-slate-200'"
                               class="flex items-center gap-2 p-3 border-2 rounded-lg cursor-pointer transition hover:border-rose-300">
                            <input type="radio" name="modo_fidelidade" value="{{ $valor }}" x-model="modo" class="text-rose-600">
                            <span class="text-sm font-medium">{{ $rotulo }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div x-show="modo === 'pontos' || modo === 'ambos'">
                    <label class="text-sm font-medium">Pontos por R$ 1 *</label>
                    <input type="number" name="pontos_por_real" step="0.01" min="0"
                           value="{{ old('pontos_por_real', $empresa->pontos_por_real ?? 1) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div x-show="modo === 'cashback' || modo === 'ambos'">
                    <label class="text-sm font-medium">Cashback (%) *</label>
                    <input type="number" name="cashback_percentual" step="0.01" min="0" max="100"
                           value="{{ old('cashback_percentual', $empresa->cashback_percentual ?? 0) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div x-show="modo === 'pontos' || modo === 'ambos'">
                    <label class="text-sm font-medium">Validade pontos (dias) *</label>
                    <input type="number" name="validade_pontos_dias" min="30"
                           value="{{ old('validade_pontos_dias', $empresa->validade_pontos_dias ?? 365) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div x-show="modo === 'cashback' || modo === 'ambos'">
                    <label class="text-sm font-medium">Dias pra liberar cashback</label>
                    <input type="number" name="dias_liberar_cashback" min="0"
                           value="{{ old('dias_liberar_cashback', $empresa->dias_liberar_cashback ?? 0) }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>
        </div>

        @if (!$empresa->exists)
            <h3 class="font-semibold mt-6 mb-4 text-slate-700">Admin inicial da empresa</h3>
            <p class="text-xs text-slate-500 mb-3">Esse usuário poderá gerenciar a empresa pelo painel admin.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium">Nome *</label>
                    <input type="text" name="admin_nome" required value="{{ old('admin_nome') }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="text-sm font-medium">E-mail *</label>
                    <input type="email" name="admin_email" required value="{{ old('admin_email') }}"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="text-sm font-medium">Senha *</label>
                    <input type="text" name="admin_password" required minlength="6"
                           class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>
        @else
            <label class="flex items-center gap-2 mt-4">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $empresa->ativo) ? 'checked' : '' }} class="rounded">
                <span class="text-sm">Empresa ativa</span>
            </label>
        @endif

        <div class="flex gap-2 mt-6">
            <button class="px-5 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
                {{ $empresa->exists ? 'Salvar alterações' : 'Cadastrar empresa' }}
            </button>
            <a href="{{ route('super.empresas.index') }}" class="px-5 py-2 bg-slate-200 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>

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
