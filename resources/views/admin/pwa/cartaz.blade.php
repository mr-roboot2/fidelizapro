<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cartaz - {{ $empresa->nome }}</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
    @page { size: A4 portrait; margin: 12mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #0f172a;
        background: #f1f5f9;
        min-height: 100vh;
        padding: 24px;
    }
    .cartaz {
        max-width: 720px;
        margin: 0 auto;
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,.08);
        border: 2px dashed #cbd5e1;
    }
    .topo {
        padding: 28px 32px 22px;
        background: linear-gradient(135deg, {{ $empresa->cor_primaria ?? '#6366f1' }} 0%, #ec4899 100%);
        color: #fff;
        text-align: center;
    }
    .topo .logo-wrap {
        width: 70px; height: 70px; margin: 0 auto 12px;
        background: rgba(255,255,255,.18);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .topo .logo-wrap img { max-width: 70%; max-height: 70%; object-fit: contain; }
    .topo .logo-wrap .inicial { font-size: 32px; font-weight: 800; }
    .topo h1 { font-size: 26px; font-weight: 800; letter-spacing: -0.02em; }
    .topo .subtitulo { font-size: 13px; opacity: .85; margin-top: 4px; }

    .titulo-chamativo {
        text-align: center;
        padding: 26px 24px 14px;
    }
    .titulo-chamativo .badge {
        display: inline-block;
        background: #fef3c7;
        color: #92400e;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 4px 10px;
        border-radius: 999px;
        margin-bottom: 10px;
    }
    .titulo-chamativo h2 {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -0.025em;
        line-height: 1.15;
        color: #0f172a;
    }
    .titulo-chamativo h2 span { color: {{ $empresa->cor_primaria ?? '#6366f1' }}; }
    .titulo-chamativo h2 u { text-decoration-color: #f59e0b; text-decoration-thickness: 3px; text-underline-offset: 4px; }
    .titulo-chamativo p {
        font-size: 14px;
        color: #475569;
        margin-top: 10px;
        line-height: 1.45;
    }

    .qr-area {
        display: flex; justify-content: center; padding: 8px 24px 20px;
    }
    .qr-box {
        background: #fff;
        border: 8px solid #fff;
        border-radius: 16px;
        box-shadow: 0 0 0 2px {{ $empresa->cor_primaria ?? '#6366f1' }}33;
        padding: 12px;
    }
    .qr-box svg { display: block; width: 320px; height: 320px; }

    .passos {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        padding: 0 24px 18px;
    }
    .passo {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        text-align: center;
    }
    .passo .num {
        width: 32px; height: 32px;
        background: {{ $empresa->cor_primaria ?? '#6366f1' }};
        color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800;
        margin: 0 auto 8px;
    }
    .passo .label {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
    }
    .passo .sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }

    .rodape {
        background: #0f172a;
        color: #fff;
        padding: 18px 24px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
    }
    .rodape .url {
        font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        font-size: 12px;
        opacity: .85;
        word-break: break-all;
    }
    .rodape .marca {
        font-size: 11px; opacity: .65;
    }

    .acoes {
        max-width: 720px;
        margin: 18px auto 0;
        display: flex; gap: 10px; justify-content: center;
    }
    .acoes button {
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }
    .acoes .primary { background: #0f172a; color: #fff; }
    .acoes .ghost { background: #fff; color: #0f172a; border: 1px solid #cbd5e1; }

    @media print {
        body { background: #fff; padding: 0; }
        .cartaz {
            border: none; box-shadow: none; border-radius: 0;
            max-width: none;
        }
        .acoes { display: none; }
    }
</style>
</head>
<body>

<div class="cartaz">
    <div class="topo">
        <div class="logo-wrap">
            @if (!empty($empresa->logo))
                <img src="{{ asset('storage/'.$empresa->logo) }}" alt="{{ $empresa->nome }}">
            @else
                <span class="inicial">{{ mb_strtoupper(mb_substr($empresa->nome, 0, 1)) }}</span>
            @endif
        </div>
        <h1>{{ $empresa->nome }}</h1>
        <div class="subtitulo">Programa de fidelidade</div>
    </div>

    <div class="titulo-chamativo">
        <div class="badge"><i class="ri-vip-crown-line"></i> Programa de fidelidade</div>
        <h2>
            Participe e
            @if ($empresa->usaPontos() && $empresa->usaCashback())
                <span>ganhe pontos e cashback</span>
            @elseif ($empresa->usaCashback())
                <span>ganhe cashback de volta</span>
            @else
                <span>acumule pontos</span>
            @endif
            <br>em <u>cada compra</u> que fizer!
        </h2>
        <p>
            @if ($empresa->usaPontos() && $empresa->usaCashback())
                Junte pontos e cashback e troque por descontos, brindes e recompensas exclusivas.
            @elseif ($empresa->usaCashback())
                Receba uma % de volta a cada compra pra usar como desconto na próxima.
            @else
                Cada compra vale pontos. Troque por descontos, brindes e recompensas exclusivas.
            @endif
        </p>
        <p style="margin-top:10px; font-weight:600; color:#0f172a;">
            <i class="ri-gift-fill" style="color:{{ $empresa->cor_primaria ?? '#6366f1' }}"></i>
            É <strong>grátis</strong>, leva <strong>30 segundos</strong> e os benefícios começam <strong>agora</strong>.
        </p>
    </div>

    <div class="qr-area">
        <div class="qr-box">{!! $qrSvg !!}</div>
    </div>

    <div class="passos">
        <div class="passo">
            <div class="num">1</div>
            <div class="label">Escaneie o QR</div>
            <div class="sub">Câmera do seu celular</div>
        </div>
        <div class="passo">
            <div class="num">2</div>
            <div class="label">Cadastre-se</div>
            <div class="sub">Nome e WhatsApp, só isso</div>
        </div>
        <div class="passo">
            <div class="num">3</div>
            <div class="label">Aproveite</div>
            <div class="sub">Benefícios a cada compra</div>
        </div>
    </div>

    <div style="text-align:center; padding: 0 24px 22px;">
        <div style="display:inline-flex; align-items:center; gap:8px; background: #fef3c7; color:#92400e; padding: 10px 16px; border-radius: 999px; font-size: 13px; font-weight: 700;">
            <i class="ri-flashlight-fill"></i>
            Diga ao caixa quando finalizar — ele credita seus benefícios!
        </div>
    </div>

    <div class="rodape">
        <div class="url">{{ $url }}</div>
        <div class="marca">FidelizaPro</div>
    </div>
</div>

<div class="acoes">
    <button class="primary" onclick="window.print()"><i class="ri-printer-line"></i> Imprimir</button>
    <button class="ghost" onclick="window.close()">Fechar</button>
</div>

<script>
    if (new URLSearchParams(location.search).has('auto')) {
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    }
</script>

</body>
</html>
