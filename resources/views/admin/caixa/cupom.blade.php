<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cupom #{{ $compra->id }}</title>
<style>
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: "Consolas", "Courier New", ui-monospace, monospace;
        font-size: 12px;
        line-height: 1.35;
        color: #000;
        background: #e2e8f0;
        padding: 16px 0;
    }
    .cupom {
        width: 80mm;
        max-width: 80mm;
        margin: 0 auto 16px;
        padding: 6mm 4mm;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
    }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: 700; }
    .big { font-size: 14px; }
    .small { font-size: 10px; }
    .muted { color: #444; }
    .row { display: flex; justify-content: space-between; gap: 8px; }
    .row > span:last-child { text-align: right; }
    hr {
        border: none;
        border-top: 1px dashed #000;
        margin: 4px 0;
    }
    .sep-vias {
        text-align: center;
        font-size: 10px;
        letter-spacing: 2px;
        margin: 8px 0;
        color: #555;
    }
    .sep-vias::before, .sep-vias::after {
        content: "✂ - - - - - - - - - - - - - - - - - - - -";
        display: block;
    }
    .via-tag {
        text-align: center;
        background: #000;
        color: #fff;
        padding: 2px 0;
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }
    .destaque {
        border: 1px dashed #000;
        padding: 4px 6px;
        margin: 4px 0;
    }

    /* Toolbar só na tela */
    .toolbar {
        max-width: 80mm;
        margin: 0 auto 12px;
        display: flex; gap: 6px; justify-content: center;
        font-family: -apple-system, "Segoe UI", Roboto, sans-serif;
        font-size: 13px;
    }
    .toolbar button {
        padding: 8px 14px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }
    .toolbar .primary { background: #0f172a; color: #fff; }
    .toolbar .ghost { background: #fff; color: #0f172a; border: 1px solid #cbd5e1; }
    .dica {
        max-width: 80mm; margin: 0 auto 14px;
        padding: 8px 12px;
        background: #fef3c7; color: #92400e;
        border-radius: 6px; font-size: 11px;
        font-family: -apple-system, "Segoe UI", Roboto, sans-serif;
        text-align: center; line-height: 1.4;
    }

    @media print {
        body { background: #fff; padding: 0; }
        .toolbar, .dica { display: none !important; }
        .cupom { box-shadow: none; margin: 0; padding: 4mm 4mm; }
    }
</style>
</head>
<body>

@php
    $e = $compra->empresa;
    $c = $compra->cliente;
    $u = $compra->user;
    $valorBruto = (float) $compra->valor;
    $desconto   = (float) $compra->desconto;
    $valorPago  = max($valorBruto - $desconto, 0);
    $vias = [
        ['titulo' => 'VIA DA LOJA',    'rodape' => 'Guarde este comprovante.'],
        ['titulo' => 'VIA DO CLIENTE', 'rodape' => 'Obrigado pela preferência!'],
    ];
@endphp

<div class="toolbar">
    <button class="primary" onclick="window.print()">🖨 Imprimir</button>
    <button class="ghost" onclick="window.close()">Fechar</button>
</div>
<div class="dica">
    Configure a impressora térmica como destino e <strong>desmarque "Cabeçalhos e rodapés"</strong>.
</div>

@foreach ($vias as $i => $via)
<div class="cupom">
    <div class="via-tag">{{ $via['titulo'] }}</div>

    <div class="center bold big">{{ mb_strtoupper($e->nome) }}</div>
    @if ($e->endereco)
        <div class="center small muted">{{ $e->endereco }}</div>
    @endif
    @if ($e->cnpj)
        <div class="center small muted">CNPJ: {{ $e->cnpj }}</div>
    @endif
    @if ($e->telefone)
        <div class="center small muted">Tel: {{ $e->telefone }}</div>
    @endif

    <hr>
    <div class="center bold">COMPROVANTE DE COMPRA</div>
    <div class="center small">Programa de fidelidade</div>
    <hr>

    <div class="row"><span>Nº</span><span class="bold">#{{ str_pad($compra->id, 6, '0', STR_PAD_LEFT) }}</span></div>
    <div class="row"><span>Data</span><span>{{ $compra->created_at->format('d/m/Y H:i') }}</span></div>
    @if ($u)
        <div class="row"><span>Operador</span><span>{{ \Illuminate\Support\Str::limit($u->name, 18, '') }}</span></div>
    @endif

    <hr>
    <div class="bold">CLIENTE</div>
    <div>{{ $c->nome }}</div>
    <div class="small muted">{{ $c->telefone }}</div>
    @if ($c->cpf)
        <div class="small muted">CPF: {{ $c->cpf }}</div>
    @endif

    <hr>
    @if ($compra->descricao)
        <div class="bold">DESCRIÇÃO</div>
        <div>{{ $compra->descricao }}</div>
        <hr>
    @endif

    <div class="row"><span>Subtotal</span><span>R$ {{ number_format($valorBruto, 2, ',', '.') }}</span></div>
    @if ($desconto > 0)
        <div class="row"><span>Cashback usado</span><span>- R$ {{ number_format($desconto, 2, ',', '.') }}</span></div>
    @endif
    <div class="row big bold"><span>TOTAL PAGO</span><span>R$ {{ number_format($valorPago, 2, ',', '.') }}</span></div>

    @if ($compra->pontos_gerados > 0 || $compra->cashback_gerado > 0)
    <div class="destaque">
        <div class="center bold small">VOCÊ GANHOU NESTA COMPRA</div>
        @if ($compra->pontos_gerados > 0)
            <div class="row"><span>Pontos</span><span class="bold">+{{ number_format($compra->pontos_gerados, 0, ',', '.') }}</span></div>
        @endif
        @if ($compra->cashback_gerado > 0)
            <div class="row"><span>Cashback</span><span class="bold">+R$ {{ number_format($compra->cashback_gerado, 2, ',', '.') }}</span></div>
        @endif
    </div>
    @endif

    <hr>
    <div class="bold small">SEU SALDO ATUAL</div>
    @if ($e->usaPontos())
        <div class="row"><span>Pontos</span><span class="bold">{{ number_format($c->pontos_atual, 0, ',', '.') }}</span></div>
    @endif
    @if ($e->usaCashback())
        <div class="row"><span>Cashback</span><span class="bold">R$ {{ number_format($c->cashback_atual, 2, ',', '.') }}</span></div>
        @if ($c->cashback_pendente > 0)
            <div class="row small muted"><span>(pendente)</span><span>R$ {{ number_format($c->cashback_pendente, 2, ',', '.') }}</span></div>
        @endif
    @endif

    <hr>
    <div class="center small">
        Veja seus benefícios no app:<br>
        <strong>{{ url('/app/'.$e->slug) }}</strong>
    </div>

    <hr>
    <div class="center small muted">{{ $via['rodape'] }}</div>
    <div class="center small muted">{{ now()->format('d/m/Y H:i:s') }}</div>
</div>

@if ($i === 0)
    <div class="sep-vias">RASGAR AQUI</div>
@endif
@endforeach

<script>
    if (new URLSearchParams(location.search).has('auto')) {
        window.addEventListener('load', () => setTimeout(() => window.print(), 250));
    }
</script>

</body>
</html>
