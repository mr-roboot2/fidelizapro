<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook da WhatsApp Cloud API (Meta) — global, uma WABA pra todas
 * as empresas do SaaS.
 *
 * GET  /webhook/whatsapp/meta - handshake de verificação
 * POST /webhook/whatsapp/meta - recebe eventos
 */
class WhatsappWebhookController extends Controller
{
    public function verificar(Request $request)
    {
        $config = ConfiguracaoSistema::instancia();

        $mode      = $request->query('hub_mode')         ?? $request->query('hub.mode');
        $token     = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge')    ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token && hash_equals((string) $config->whatsapp_webhook_verify_token, (string) $token)) {
            Log::info('[Meta Webhook] Verificado com sucesso');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('[Meta Webhook] Falha na verificação', [
            'mode' => $mode,
        ]);
        return response('Forbidden', 403);
    }

    public function receber(Request $request)
    {
        // Logamos apenas metadados — payloads de WhatsApp contêm PII (telefone, texto)
        Log::info('[Meta Webhook] Evento recebido', [
            'object'  => $request->input('object'),
            'entry_id'=> $request->input('entry.0.id'),
            'changes' => count($request->input('entry.0.changes', [])),
        ]);
        return response()->json(['ok' => true]);
    }
}
