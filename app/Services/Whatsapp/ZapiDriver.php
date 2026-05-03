<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Z-API (z-api.io) — provider brasileiro popular.
 * Docs: https://developer.z-api.io/
 */
class ZapiDriver implements WhatsappDriverInterface
{
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): bool
    {
        if (!$config->whatsapp_instance || !$config->whatsapp_api_token) {
            Log::warning("[Z-API] Configuração global incompleta");
            return false;
        }

        $base = $config->whatsapp_api_url ?: 'https://api.z-api.io';
        // Z-API: Client-Token (account-level) é distinto do token da instância
        $clientToken = $config->whatsapp_client_token ?: $config->whatsapp_api_token;

        try {
            $response = Http::withHeaders([
                'Client-Token' => $clientToken,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(
                rtrim($base, '/')."/instances/{$config->whatsapp_instance}/token/{$config->whatsapp_api_token}/send-text",
                [
                    'phone' => $this->normalizar($telefone),
                    'message' => $mensagem,
                ]
            );

            if (!$response->successful()) {
                Log::warning("[Z-API] Falha enviando para {$telefone}: ".$response->body());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("[Z-API] Exceção: ".$e->getMessage());
            return false;
        }
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        $ok = $this->enviar($config, $telefoneDestino, "[Teste de conexão WhatsApp via Z-API - {$config->nome_sistema}]");
        return [
            'ok' => $ok,
            'mensagem' => $ok ? 'Mensagem de teste enviada!' : 'Falha — confira instance ID e token nos logs.',
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
