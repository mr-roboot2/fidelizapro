<?php

namespace App\Http\Controllers;

use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
use App\Services\AssinaturaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookPagamentoController extends Controller
{
    /**
     * POST /webhook/pagamento/{gateway}
     *
     * Asaas envia header `asaas-access-token` configurável no painel deles —
     * comparamos com o valor salvo em configuracoes_sistema.asaas_webhook_token
     * usando hash_equals (timing-safe). Sem token configurado a requisição é
     * rejeitada para evitar webhook forjado marcando cobranças como pagas.
     */
    public function receber(Request $request, string $gateway, AssinaturaService $service)
    {
        if ($gateway === 'asaas') {
            $esperado = (string) (ConfiguracaoSistema::instancia()->asaas_webhook_token ?? '');
            // Asaas envia o token no header "asaas-access-token". Aceitamos
            // também algumas variações comuns por segurança (alguns proxies
            // normalizam underscores; raros gateways usam outro nome).
            $recebido = (string) (
                $request->header('asaas-access-token')
                ?? $request->header('Asaas-Access-Token')
                ?? $request->header('access_token')
                ?? ''
            );

            if (!$esperado || !hash_equals($esperado, $recebido)) {
                // Diagnóstico: aponta a primeira posição que diverge e os
                // códigos ASCII de cada lado (ajuda identificar invisíveis,
                // encoding ou char alterado). Não loga o token completo.
                $diffPos = -1; $ordEsp = null; $ordRec = null;
                $tam = max(strlen($esperado), strlen($recebido));
                for ($i = 0; $i < $tam; $i++) {
                    $a = $esperado[$i] ?? '';
                    $b = $recebido[$i] ?? '';
                    if ($a !== $b) {
                        $diffPos = $i;
                        $ordEsp  = $a === '' ? null : ord($a);
                        $ordRec  = $b === '' ? null : ord($b);
                        break;
                    }
                }

                Log::warning("[Webhook {$gateway}] Assinatura inválida", [
                    'ip'              => $request->ip(),
                    'header_presente' => $recebido !== '',
                    'esperado_len'    => strlen($esperado),
                    'recebido_len'    => strlen($recebido),
                    'esperado_prefix' => substr($esperado, 0, 4),
                    'recebido_prefix' => substr($recebido, 0, 4),
                    'esperado_suffix' => substr($esperado, -4),
                    'recebido_suffix' => substr($recebido, -4),
                    'diff_pos'        => $diffPos,
                    'esperado_ord'    => $ordEsp,
                    'recebido_ord'    => $ordRec,
                    // md5 dos tokens permite comparar sem expor — se os md5
                    // forem iguais, hash_equals deveria passar (é bug nosso).
                    'esperado_md5'    => md5($esperado),
                    'recebido_md5'    => md5($recebido),
                ]);
                return response()->json(['error' => 'invalid_signature'], 401);
            }
        }

        Log::info("[Webhook {$gateway}] Recebido", [
            'event'             => $request->input('event'),
            'payment_id'        => $request->input('payment.id'),
            'payment_status'    => $request->input('payment.status'),
            'external_reference'=> $request->input('payment.externalReference'),
        ]);

        try {
            $payload = $request->all();
            $resultado = $service->gateway($gateway)->processarWebhook($payload);

            // Eventos de pagamento
            $pagouEvents = ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED', 'paid', 'payment.paid'];
            if (in_array($resultado['evento'], $pagouEvents)) {
                $cobranca = null;
                if (!empty($resultado['cobranca_id'])) {
                    $cobranca = Cobranca::find($resultado['cobranca_id']);
                } elseif (!empty($resultado['gateway_charge_id'])) {
                    $cobranca = Cobranca::where('gateway_charge_id', $resultado['gateway_charge_id'])->first();
                }

                if ($cobranca && $cobranca->status === 'pendente') {
                    $service->marcarPaga($cobranca, $resultado['gateway_charge_id'] ?? null);
                    return response()->json(['ok' => true, 'cobranca_id' => $cobranca->id]);
                }
            }

            return response()->json(['ok' => true, 'evento' => $resultado['evento'] ?? 'ignored']);
        } catch (\Throwable $e) {
            Log::error("[Webhook {$gateway}] Erro: ".$e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Tela de "pagamento mock" para dev — clica e marca como pago.
     * Restrito a ambiente local para evitar bypass de pagamento em produção.
     */
    public function pagamentoMock(int $cobrancaId, AssinaturaService $service)
    {
        abort_unless(app()->environment('local'), 404);

        $cobranca = Cobranca::with('assinatura.empresa')->findOrFail($cobrancaId);

        if ($cobranca->status === 'pago') {
            return view('pagamento_mock.ja_pago', compact('cobranca'));
        }

        return view('pagamento_mock.confirmar', compact('cobranca'));
    }

    public function confirmarPagamentoMock(int $cobrancaId, AssinaturaService $service)
    {
        abort_unless(app()->environment('local'), 404);

        $cobranca = Cobranca::findOrFail($cobrancaId);
        if ($cobranca->status !== 'pago') {
            $service->marcarPaga($cobranca);
        }
        return redirect()->route('pagamento.mock', $cobrancaId);
    }
}
