<?php

namespace App\Services\Whatsapp;

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API (oficial Meta).
 * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * Configuração esperada:
 *   whatsapp_api_token = Bearer token (System User)
 *   whatsapp_phone_id  = Phone Number ID
 */
class MetaCloudDriver implements WhatsappDriverInterface
{
    public function enviar(Empresa $empresa, string $telefone, string $mensagem): bool
    {
        if (!$empresa->whatsapp_api_token || !$empresa->whatsapp_phone_id) {
            Log::warning("[Meta Cloud] Configuração incompleta para empresa {$empresa->id}");
            return false;
        }

        try {
            $response = Http::withToken($empresa->whatsapp_api_token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v18.0/{$empresa->whatsapp_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $this->normalizar($telefone),
                    'type' => 'text',
                    'text' => ['body' => $mensagem],
                ]);

            if (!$response->successful()) {
                Log::warning("[Meta Cloud] Falha enviando para {$telefone}: ".$response->body());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("[Meta Cloud] Exceção: ".$e->getMessage());
            return false;
        }
    }

    public function testar(Empresa $empresa, string $telefoneDestino): array
    {
        $ok = $this->enviar($empresa, $telefoneDestino, "[Teste WhatsApp Cloud API - {$empresa->nome}]");
        return [
            'ok' => $ok,
            'mensagem' => $ok ? 'Mensagem de teste enviada!' : 'Falha — confira token e phone ID. Cloud API só envia para números pré-aprovados em modo dev.',
        ];
    }

    protected function normalizar(string $telefone): string
    {
        $apenas = preg_replace('/\D/', '', $telefone);
        if (strlen($apenas) === 11) $apenas = '55'.$apenas;
        if (strlen($apenas) === 10) $apenas = '55'.$apenas;
        return $apenas;
    }
}
