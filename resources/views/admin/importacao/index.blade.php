@extends('layouts.admin')
@section('title', 'Importação & Integrações')
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">

    <!-- Importação CSV -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-3 flex items-center gap-2">
            <i class="ri-file-upload-line text-indigo-600"></i> Importar compras de CSV
        </h2>
        <p class="text-sm text-slate-600 mb-4">Envie um arquivo CSV com o histórico de compras dos seus clientes.</p>

        <form action="{{ route('admin.importacao.processar') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="text-sm font-medium">Arquivo CSV</label>
                <input type="file" name="arquivo" accept=".csv,text/csv" required
                       class="mt-1 w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="criar_clientes" value="1" class="rounded">
                <span class="text-sm">Criar clientes novos automaticamente (precisa coluna <code>nome</code>)</span>
            </label>
            <button class="w-full py-2 bg-indigo-600 text-white rounded-lg">
                <i class="ri-upload-cloud-line"></i> Processar arquivo
            </button>
        </form>

        @if (session('importacao_erros') && count(session('importacao_erros')))
            <div class="mt-4 bg-rose-50 border border-rose-200 rounded-lg p-3 max-h-48 overflow-y-auto">
                <p class="font-semibold text-rose-700 text-sm mb-2">Erros encontrados:</p>
                <ul class="text-xs text-rose-600 space-y-1">
                    @foreach (session('importacao_erros') as $erro) <li>• {{ $erro }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6 p-4 bg-slate-50 rounded-lg text-xs">
            <p class="font-semibold mb-2 text-slate-700">Formato esperado (CSV separado por vírgula):</p>
            <pre class="bg-white p-2 rounded border border-slate-200 overflow-x-auto">telefone,nome,cpf,valor,descricao,codigo
(11)99999-1111,Maria Silva,123.456.789-00,150.50,Almoço,PED001
(11)99999-2222,,,87.30,Compra balcão,</pre>
            <p class="mt-2 text-slate-500">Colunas obrigatórias: <strong>telefone</strong>, <strong>valor</strong>. Demais opcionais.</p>
        </div>
    </div>

    <!-- Webhook PDV -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-lg mb-3 flex items-center gap-2">
            <i class="ri-plug-line text-emerald-600"></i> Webhook PDV
        </h2>
        <p class="text-sm text-slate-600 mb-4">Configure seu sistema de PDV para enviar compras em tempo real.</p>

        <div class="space-y-3">
            <div>
                <label class="text-xs text-slate-500">Endpoint</label>
                <div class="flex">
                    <code class="flex-1 px-3 py-2 bg-slate-100 rounded-l border border-slate-200 text-xs break-all">{{ url('/api/v1/pdv/'.auth()->user()->empresa->slug.'/compras') }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ url('/api/v1/pdv/'.auth()->user()->empresa->slug.'/compras') }}'); this.textContent='✓'"
                            class="px-3 bg-slate-200 rounded-r text-sm">Copiar</button>
                </div>
            </div>
            <div>
                <label class="text-xs text-slate-500">Header de autenticação</label>
                <div class="flex">
                    <code class="flex-1 px-3 py-2 bg-slate-100 rounded-l border border-slate-200 text-xs break-all">X-Pdv-Secret: {{ auth()->user()->empresa->pdv_secret }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ auth()->user()->empresa->pdv_secret }}'); this.textContent='✓'"
                            class="px-3 bg-slate-200 rounded-r text-sm">Copiar</button>
                </div>
            </div>

            <div class="mt-4 p-4 bg-slate-50 rounded-lg text-xs">
                <p class="font-semibold mb-2 text-slate-700">Exemplo de chamada (cURL):</p>
                <pre class="bg-white p-2 rounded border border-slate-200 overflow-x-auto text-[11px]">curl -X POST {{ url('/api/v1/pdv/'.auth()->user()->empresa->slug.'/compras') }} \
  -H "Content-Type: application/json" \
  -H "X-Pdv-Secret: {{ auth()->user()->empresa->pdv_secret }}" \
  -d '{
    "telefone": "(11) 99999-1111",
    "valor": 150.50,
    "codigo": "PED-001",
    "descricao": "Pedido balcão"
  }'</pre>
            </div>

            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded text-xs">
                <p class="font-semibold text-amber-800"><i class="ri-information-line"></i> Auto-cadastro de clientes</p>
                <p class="text-amber-700 mt-1">Se enviar <code>nome</code> + <code>telefone</code> e o cliente não existir, ele será criado automaticamente.</p>
            </div>

            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-700 mb-1">Resposta de sucesso:</p>
                <pre class="text-[11px] bg-slate-50 p-2 rounded border border-slate-200">{
  "message": "Compra registrada via PDV.",
  "cliente_criado": false,
  "cliente": { "id": 12, "nome": "...", "pontos_atual": 1500.0, "cashback_atual": 32.50 },
  "compra": { "id": 234, "valor": 150.5, "pontos_gerados": 150.0, "cashback_gerado": 4.50 }
}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
