<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook da WhatsApp Cloud API (Meta).
 *
 * GET  /webhook/whatsapp/meta/{slug} - handshake de verificação
 * POST /webhook/whatsapp/meta/{slug} - recebe eventos (mensagens, status)
 *
 * Cada empresa tem seu próprio verify_token (whatsapp_webhook_verify_token).
 */
class WhatsappWebhookController extends Controller
{
    public function verificar(Request $request, string $slug)
    {
        $empresa = Empresa::where('slug', $slug)->first();
        if (!$empresa) {
            return response('Not found', 404);
        }

        $mode      = $request->query('hub_mode')         ?? $request->query('hub.mode');
        $token     = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge')    ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token && hash_equals((string) $empresa->whatsapp_webhook_verify_token, (string) $token)) {
            Log::info("[Meta Webhook] Verificado com sucesso para {$slug}");
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("[Meta Webhook] Falha na verificação para {$slug}", [
            'mode' => $mode, 'token_recebido' => $token,
        ]);
        return response('Forbidden', 403);
    }

    public function receber(Request $request, string $slug)
    {
        $empresa = Empresa::where('slug', $slug)->first();
        if (!$empresa) {
            return response()->json(['ok' => true]);
        }

        $payload = $request->all();

        // Por enquanto: registra o evento. Processamento (mensagens entrantes,
        // atualização de status de envio, etc.) pode ser feito de forma
        // incremental conforme for usado.
        Log::info("[Meta Webhook] Evento recebido para {$slug}", [
            'empresa_id' => $empresa->id,
            'payload'    => $payload,
        ]);

        // Meta requer 200 OK em até 5s — caso contrário desabilita o webhook.
        return response()->json(['ok' => true]);
    }
}
