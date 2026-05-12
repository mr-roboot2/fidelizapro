<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatório de resgates - {{ $empresa->nome }}</title>
<style>
    @page { size: A4 portrait; margin: 10mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, "Segoe UI", Roboto, sans-serif;
        font-size: 11px;
        color: #0f172a;
        background: #f1f5f9;
        padding: 16px;
    }
    .container { max-width: 1100px; margin: 0 auto; }
    .doc {
        background: #fff;
        padding: 18px 20px;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    h1 { font-size: 18px; margin-bottom: 4px; }
    .header-doc {
        display: flex; justify-content: space-between; align-items: flex-end;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .header-doc .empresa { font-size: 16px; font-weight: 800; }
    .header-doc .meta { font-size: 10px; color: #475569; text-align: right; line-height: 1.5; }
    .stats {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
        margin-bottom: 14px;
    }
    .stats .card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px;
    }
    .stats .card .label {
        font-size: 9px;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.04em;
    }
    .stats .card .valor {
        font-size: 16px;
        font-weight: 800;
        margin-top: 2px;
    }
    .stats .card.entregue .valor { color: #059669; }
    .stats .card.pendente .valor { color: #d97706; }
    .stats .card.aprovado .valor { color: #2563eb; }
    .stats .card.cancelado .valor { color: #dc2626; }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }
    th {
        background: #0f172a;
        color: #fff;
        text-align: left;
        padding: 6px 8px;
        font-weight: 600;
        font-size: 10px;
        white-space: nowrap;
    }
    td {
        padding: 6px 8px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: top;
    }
    tr:nth-child(even) td { background: #f8fafc; }
    .codigo {
        font-family: ui-monospace, "Cascadia Code", monospace;
        font-weight: 700;
        font-size: 10px;
    }
    .badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .b-entregue { background: #d1fae5; color: #065f46; }
    .b-pendente { background: #fef3c7; color: #92400e; }
    .b-aprovado { background: #dbeafe; color: #1e3a8a; }
    .b-cancelado { background: #fee2e2; color: #991b1b; }
    .small { font-size: 9px; color: #475569; }
    .center { text-align: center; }
    .right  { text-align: right; }

    .footer-doc {
        margin-top: 18px;
        padding-top: 8px;
        border-top: 1px solid #cbd5e1;
        display: flex;
        justify-content: space-between;
        font-size: 9px;
        color: #475569;
    }
    .auditoria-note {
        margin-top: 12px;
        padding: 8px 10px;
        background: #fef9c3;
        border: 1px solid #fde68a;
        border-radius: 6px;
        font-size: 9px;
        color: #713f12;
        line-height: 1.5;
    }

    .toolbar {
        max-width: 1100px;
        margin: 0 auto 12px;
        display: flex; gap: 8px; justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .toolbar form { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; font-size: 12px; }
    .toolbar input, .toolbar select {
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        font-size: 12px;
    }
    .toolbar .btns { display: flex; gap: 6px; }
    .toolbar button, .toolbar a.btn {
        padding: 7px 14px;
        border-radius: 6px;
        border: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-primary { background: #0f172a; color: #fff; }
    .btn-ghost   { background: #fff; color: #0f172a; border: 1px solid #cbd5e1; }
    .btn-filter  { background: #6366f1; color: #fff; }

    @media print {
        body { background: #fff; padding: 0; }
        .toolbar { display: none !important; }
        .doc { box-shadow: none; border-radius: 0; padding: 0; }
        table { font-size: 9.5px; }
        tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <form method="GET" action="{{ route('admin.resgates.relatorio') }}">
        <label>De: <input type="date" name="de" value="{{ $de->toDateString() }}"></label>
        <label>Até: <input type="date" name="ate" value="{{ $ate->toDateString() }}"></label>
        <select name="status">
            <option value="">Todos os status</option>
            @foreach (['pendente','aprovado','entregue','cancelado'] as $s)
                <option value="{{ $s }}" @selected($request->input('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn-filter" type="submit">Filtrar</button>
    </form>
    <div class="btns">
        <a href="{{ route('admin.resgates.index') }}" class="btn btn-ghost">← Voltar</a>
        <button class="btn-primary" onclick="window.print()">🖨 Imprimir</button>
    </div>
</div>

<div class="container">
<div class="doc">

    <div class="header-doc">
        <div>
            <h1>Relatório de resgates</h1>
            <div class="empresa">{{ $empresa->nome }}</div>
        </div>
        <div class="meta">
            <div><strong>Período:</strong> {{ $de->format('d/m/Y') }} a {{ $ate->format('d/m/Y') }}</div>
            @if ($request->input('status'))
                <div><strong>Status:</strong> {{ ucfirst($request->input('status')) }}</div>
            @endif
            <div>Emitido em {{ now()->format('d/m/Y H:i') }}</div>
            <div>Por: {{ Auth::user()->name }}</div>
        </div>
    </div>

    <div class="stats">
        <div class="card">
            <div class="label">Total de resgates</div>
            <div class="valor">{{ $stats['total'] }}</div>
        </div>
        <div class="card entregue">
            <div class="label">Entregues</div>
            <div class="valor">{{ $stats['entregues'] }}</div>
        </div>
        <div class="card aprovado">
            <div class="label">Aprovados</div>
            <div class="valor">{{ $stats['aprovados'] }}</div>
        </div>
        <div class="card pendente">
            <div class="label">Pendentes</div>
            <div class="valor">{{ $stats['pendentes'] }}</div>
        </div>
        <div class="card cancelado">
            <div class="label">Cancelados</div>
            <div class="valor">{{ $stats['cancelados'] }}</div>
        </div>
        <div class="card">
            <div class="label">Pontos resgatados</div>
            <div class="valor">{{ number_format($stats['pontos_total'], 0, ',', '.') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Solicitado em</th>
                <th>Cliente</th>
                <th>Recompensa</th>
                <th class="right">Pontos</th>
                <th>Status</th>
                <th>Aprovado por</th>
                <th>Entregue por</th>
                <th>IP / Observações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resgates as $r)
                <tr>
                    <td class="codigo">{{ $r->codigo }}</td>
                    <td>
                        {{ $r->created_at->format('d/m/Y') }}<br>
                        <span class="small">{{ $r->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <strong>{{ $r->cliente->nome ?? '—' }}</strong><br>
                        <span class="small">{{ $r->cliente->telefone ?? '' }}</span>
                        @if ($r->cliente?->cpf)
                            <br><span class="small">CPF {{ $r->cliente->cpf }}</span>
                        @endif
                    </td>
                    <td>{{ $r->recompensa->nome ?? '—' }}</td>
                    <td class="right"><strong>{{ number_format($r->pontos_usados, 0, ',', '.') }}</strong></td>
                    <td>
                        @php $cls = ['entregue'=>'b-entregue','pendente'=>'b-pendente','aprovado'=>'b-aprovado','cancelado'=>'b-cancelado']; @endphp
                        <span class="badge {{ $cls[$r->status] ?? '' }}">{{ $r->status }}</span>
                    </td>
                    <td>
                        @if ($r->aprovador)
                            {{ $r->aprovador->name }}<br>
                            <span class="small">{{ $r->aprovado_em?->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="small">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($r->entregador)
                            {{ $r->entregador->name }}<br>
                            <span class="small">{{ $r->entregue_em?->format('d/m/Y H:i') }}</span>
                        @elseif ($r->entregue_em)
                            <span class="small">(legado) {{ $r->entregue_em->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="small">—</span>
                        @endif
                    </td>
                    <td class="small">
                        @if ($r->ip)<div>IP: {{ $r->ip }}</div>@endif
                        @if ($r->observacao)<div>{{ \Illuminate\Support\Str::limit($r->observacao, 80) }}</div>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="center" style="padding:20px;color:#94a3b8">Nenhum resgate no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="auditoria-note">
        <strong>🔒 Trilha de auditoria:</strong> este relatório registra cada resgate com quem aprovou,
        quem entregou e em que horário — informações imutáveis no banco. Resgates entregues antes da
        atualização do sistema podem aparecer sem o nome de quem entregou (marcados como "legado").
    </div>

    <div class="footer-doc">
        <div>{{ $empresa->nome }} · Relatório gerado pelo sistema SatisFy</div>
        <div>Página 1 · {{ $resgates->count() }} registros</div>
    </div>

</div>
</div>

</body>
</html>
