<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use App\Services\Pix\PixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PixWebhookController extends Controller
{
    /**
     * POST /webhook/pix/{token}
     * Token único gerado em pix_webhook_token (ConfiguracaoSistema). Configura
     * essa URL completa no painel do gateway.
     */
    public function receber(string $token, Request $request, PixService $pix)
    {
        $esperado = ConfiguracaoSistema::instancia()->pix_webhook_token;
        if (!$esperado || !hash_equals($esperado, $token)) {
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
            Log::error('[PIX webhook] Erro: '.$e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
