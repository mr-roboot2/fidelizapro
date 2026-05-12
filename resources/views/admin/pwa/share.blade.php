@extends('layouts.admin')
@section('title', 'Compartilhar PWA')
@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    @php $vemDoSetup = url()->previous() && str_contains(url()->previous(), '/admin/setup'); @endphp
    @if ($vemDoSetup)
        <a href="{{ route('admin.setup.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
            <i class="ri-arrow-left-line"></i> Voltar ao setup
        </a>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-br from-indigo-50 to-purple-50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-xl flex items-center justify-center text-2xl">
                    <i class="ri-smartphone-line"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Seu app para clientes</h1>
                    <p class="text-sm text-slate-600">Cole o QR code na loja e divulgue o link nas redes.</p>
                </div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- QR --}}
            <div class="bg-slate-50 rounded-xl p-6 flex flex-col items-center justify-center text-center">
                <div class="bg-white p-4 rounded-xl border border-slate-200 mb-3">
                    {!! $qrSvg !!}
                </div>
                <div class="text-xs text-slate-500">QR para impressão</div>
                <button onclick="window.print()"
                        class="mt-3 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-medium">
                    <i class="ri-printer-line"></i> Imprimir
                </button>
            </div>

            {{-- LINK --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-500 mb-1 font-semibold">Link do app</label>
                    <div class="flex items-stretch gap-2">
                        <input type="text" value="{{ $url }}" readonly id="urlPwa"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono text-slate-700">
                        <button onclick="copiarUrl()"
                                class="px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium"
                                title="Copiar">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>
                </div>

                <a href="{{ $url }}" target="_blank"
                   class="block w-full text-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                    <i class="ri-external-link-line"></i> Abrir o app agora
                </a>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                    <div class="font-semibold mb-1"><i class="ri-lightbulb-flash-line"></i> Dica</div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs">
                        <li>Imprima o QR e cole no caixa, parede ou mesa.</li>
                        <li>Compartilhe o link via WhatsApp pra clientes antigos.</li>
                        <li>Quem abrir vê seu logo e suas cores — é seu app white-label.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function copiarUrl() {
    const el = document.getElementById('urlPwa');
    el.select();
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = event.currentTarget;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="ri-check-line"></i>';
        setTimeout(() => btn.innerHTML = original, 1200);
    });
}
</script>

<style>
@media print {
    nav, aside, header, .no-print, button, a[href]:not([data-print]) { display: none !important; }
    body { background: white; }
}
</style>

@endsection
