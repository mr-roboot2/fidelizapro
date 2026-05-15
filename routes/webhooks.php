<?php

/**
 * Rotas de webhook — registradas FORA do grupo `web` pra não criar
 * session file em cada request. Antes, atacante mandava milhares de POST
 * inválidos em /webhook/* e o middleware StartSession (do grupo web)
 * criava arquivo em storage/framework/sessions/ ANTES do controller
 * rejeitar com 401 → inode exhaustion / disco cheio.
 *
 * Aqui usa middleware group `api` (stateless: ThrottleRequests +
 * SubstituteBindings). Sem CSRF, sem cookies, sem session.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookPagamentoController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\PixWebhookController;

// Webhooks de gateway de pagamento. `where('gateway','asaas')` impede
// fallback no MockGateway via slugs forjados.
Route::post('/webhook/pagamento/{gateway}', [WebhookPagamentoController::class, 'receber'])
    ->where('gateway', 'asaas')
    ->name('webhook.pagamento');

// PIX webhook — preferência é header X-Pix-Webhook-Token. Variante com
// token na URL mantida pra retrocompat com gateways que só aceitam URL.
Route::post('/webhook/pix', [PixWebhookController::class, 'receber'])
    ->name('webhook.pix');
Route::post('/webhook/pix/{token}', [PixWebhookController::class, 'receber'])
    ->name('webhook.pix.legacy');

// WhatsApp Cloud (Meta) — GET pra verify handshake, POST pra eventos
// (HMAC SHA256 validado quando whatsapp_app_secret está configurado).
Route::get('/webhook/whatsapp/meta',  [WhatsappWebhookController::class, 'verificar'])
    ->name('webhook.whatsapp.verificar');
Route::post('/webhook/whatsapp/meta', [WhatsappWebhookController::class, 'receber'])
    ->name('webhook.whatsapp.receber');
