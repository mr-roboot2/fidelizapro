<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>AI Growth — {{ $empresaNome }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
        .sub { color: #64748b; font-size: 10px; }
        .kpi-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; margin: 12px 0; }
        .kpi { display: table-cell; width: 25%; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .kpi .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .kpi .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        .verde { color: #059669; font-weight: bold; }
        .vermelho { color: #dc2626; font-weight: bold; }
        .ambar { color: #d97706; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $empresaNome }} — Relatório AI Growth</h1>
    <p class="sub">Período: {{ $de->format('d/m/Y') }} a {{ $ate->format('d/m/Y') }} · Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Faturamento</div>
            <div class="value">R$ {{ number_format($kpi['faturamento'], 2, ',', '.') }}</div>
        </div>
        <div class="kpi">
            <div class="label">Vendas</div>
            <div class="value">{{ number_format($kpi['vendas'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi">
            <div class="label">Ticket médio</div>
            <div class="value">R$ {{ number_format($kpi['ticket_medio'], 2, ',', '.') }}</div>
        </div>
        <div class="kpi">
            <div class="label">Clientes únicos</div>
            <div class="value">{{ $kpi['clientes_unicos'] }}</div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Pontos gerados</div>
            <div class="value">{{ number_format($kpi['pontos_gerados'], 0, ',', '.') }}</div>
        </div>
        <div class="kpi">
            <div class="label">Cashback gerado</div>
            <div class="value">R$ {{ number_format($kpi['cashback_gerado'], 2, ',', '.') }}</div>
        </div>
        <div class="kpi">
            <div class="label">Novos clientes</div>
            <div class="value">{{ $kpi['novos_clientes'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">NPS</div>
            <div class="value">{{ $nps['nps'] }} ({{ $nps['total'] }} resp.)</div>
        </div>
    </div>

    <h2>Top 5 dias do período</h2>
    <table>
        <thead><tr><th>Dia</th><th class="num">Vendas</th><th class="num">Faturamento</th></tr></thead>
        <tbody>
            @forelse ($topDias as $d)
                <tr><td>{{ $d['dia'] }}</td><td class="num">{{ $d['vendas'] }}</td><td class="num verde">R$ {{ number_format($d['total'], 2, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#94a3b8">Sem vendas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>5 dias mais fracos</h2>
    <table>
        <thead><tr><th>Dia</th><th class="num">Vendas</th><th class="num">Faturamento</th></tr></thead>
        <tbody>
            @forelse ($bottomDias as $d)
                <tr><td>{{ $d['dia'] }}</td><td class="num">{{ $d['vendas'] }}</td><td class="num vermelho">R$ {{ number_format($d['total'], 2, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#94a3b8">Sem vendas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Faturamento médio por dia da semana (últimos 90 dias)</h2>
    <table>
        <thead><tr><th>Dia</th><th class="num">Total vendas</th><th class="num">Média/dia (R$)</th></tr></thead>
        <tbody>
            @foreach ($vendasPorDow as $dow)
                <tr><td>{{ $dow['nome'] }}</td><td class="num">{{ $dow['total_vendas'] }}</td><td class="num ambar">R$ {{ number_format($dow['media_dia'], 2, ',', '.') }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top 10 clientes do período</h2>
    <table>
        <thead><tr><th>Cliente</th><th>Telefone</th><th class="num">Compras</th><th class="num">Total</th></tr></thead>
        <tbody>
            @forelse ($topClientesPeriodo as $tc)
                <tr>
                    <td>{{ $tc->cliente->nome ?? '—' }}</td>
                    <td>{{ $tc->cliente->telefone ?? '' }}</td>
                    <td class="num">{{ $tc->qtd }}</td>
                    <td class="num verde">R$ {{ number_format($tc->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#94a3b8">Sem vendas no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Base de clientes</h2>
    <table>
        <tr><td>Total de clientes</td><td class="num">{{ $totalClientes }}</td></tr>
        <tr><td>Recorrentes (≥2 compras)</td><td class="num verde">{{ $recorrentes }}</td></tr>
        <tr><td>Taxa de retenção</td><td class="num verde">{{ $retencao }}%</td></tr>
    </table>

    <h2 style="page-break-before: always;">Vendas detalhadas do período</h2>
    <p class="sub" style="margin-bottom:6px">{{ $vendasDetalhadas->count() }} venda(s) — ordenadas da mais recente pra mais antiga</p>
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Cliente</th>
                <th>Operador</th>
                <th class="num">Valor</th>
                <th class="num">Cashback usado</th>
                <th class="num">Pontos</th>
                <th class="num">Cashback ger.</th>
                <th>Descrição</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vendasDetalhadas as $v)
                <tr>
                    <td>{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $v->cliente->nome ?? '—' }}<br><span style="font-size:9px;color:#64748b">{{ $v->cliente->telefone ?? '' }}</span></td>
                    <td>{{ $v->user->name ?? '—' }}</td>
                    <td class="num verde">R$ {{ number_format($v->valor, 2, ',', '.') }}</td>
                    <td class="num">@if ($v->desconto > 0)R$ {{ number_format($v->desconto, 2, ',', '.') }}@else —@endif</td>
                    <td class="num ambar">{{ number_format($v->pontos_gerados, 0, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($v->cashback_gerado, 2, ',', '.') }}</td>
                    <td style="font-size:9.5px">{{ \Illuminate\Support\Str::limit($v->descricao, 30) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8">Sem vendas no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 style="page-break-before: always;">Resgates do período — trilha de auditoria</h2>
    <p class="sub" style="margin-bottom:6px">{{ $resgatesDetalhados->count() }} resgate(s) — cliente, recompensa e operadores envolvidos</p>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Solicitado</th>
                <th>Cliente</th>
                <th>Recompensa</th>
                <th class="num">Pontos</th>
                <th>Status</th>
                <th>Aprovado por</th>
                <th>Entregue por</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resgatesDetalhados as $r)
                <tr>
                    <td style="font-family:monospace">{{ $r->codigo }}</td>
                    <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->cliente->nome ?? '—' }}<br><span style="font-size:9px;color:#64748b">{{ $r->cliente->telefone ?? '' }}</span></td>
                    <td>{{ $r->recompensa->nome ?? '—' }}</td>
                    <td class="num ambar">{{ number_format($r->pontos_usados, 0, ',', '.') }}</td>
                    <td>{{ $r->status }}</td>
                    <td>
                        @if ($r->aprovador){{ $r->aprovador->name }}<br><span style="font-size:9px;color:#64748b">{{ $r->aprovado_em?->format('d/m H:i') }}</span>
                        @else —@endif
                    </td>
                    <td>
                        @if ($r->entregador){{ $r->entregador->name }}<br><span style="font-size:9px;color:#64748b">{{ $r->entregue_em?->format('d/m H:i') }}</span>
                        @elseif ($r->entregue_em)<span style="font-size:9px;color:#64748b">(legado) {{ $r->entregue_em->format('d/m H:i') }}</span>
                        @else —@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8">Sem resgates no período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:8px; padding:8px; background:#fef9c3; border:1px solid #fde68a; border-radius:4px; font-size:9.5px; color:#713f12">
        <strong>🔒 Auditoria:</strong> os campos "Aprovado por" e "Entregue por" preservam o registro do
        funcionário que tomou cada ação, garantindo rastreabilidade para o dono do negócio.
    </p>

    <div class="footer">FidelizaPro · {{ $empresaNome }} · {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
