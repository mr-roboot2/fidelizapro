<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use App\Services\Pix\PixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PixWebhookController extends Controller
{
    /**
     * POST /webhook/pix              (header X-Pix-Webhook-Token: <token>)
     * POST /webhook/pix/{token}      (legacy, mantido pra gateway que só
     *                                 suporta token na URL)
     *
     * Preferência: header. Token no path vaza pra access.log do Nginx/Apache
     * e logs de proxy upstream — header não. Usa hash_equals em ambos.
     */
    public function receber(Request $request, PixService $pix, ?string $token = null)
    {
        $esperado = ConfiguracaoSistema::instancia()->pix_webhook_token;
        $recebido = $token ?? (string) $request->header('X-Pix-Webhook-Token', '');

        if (!$esperado || !hash_equals((string) $esperado, (string) $recebido)) {
            Log::warning('[PIX webhook] Token inválido', ['ip' => $request->ip()]);
            return response()->json(['error' => 'invalid token'], 403);
        }

        Log::info('[PIX webhook] Recebido', [
            'event'          => $request->input('event'),
            'payment_id'     => $request->input('payment.id'),
            'payment_status' => $request->input('payment.status'),
        ]);

        try {
            $cobranca = $pix->driver()->processarWebhook($request->all());
            if (!$cobranca) {
                return response()->json(['ok' => true, 'message' => 'evento ignorado']);
            }

            $pix->confirmarPagamento($cobranca);
            return response()->json(['ok' => true, 'cobranca_id' => $cobranca->id]);
        } catch (\Throwable $e) {
            Log::error('[PIX webhook] Erro: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'internal_error'], 500);
        }
    }
}
