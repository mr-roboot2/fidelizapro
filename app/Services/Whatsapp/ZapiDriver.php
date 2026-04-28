<?php

namespace App\Services\Whatsapp;

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Z-API (z-api.io) — provider brasileiro popular.
 * Docs: https://developer.z-api.io/
 *
 * Configuração esperada:
 *   whatsapp_api_url   = https://api.z-api.io
 *   whatsapp_instance  = sua instance ID
 *   whatsapp_api_token = seu token
 */
class ZapiDriver implements WhatsappDriverInterface
{
    public function enviar(Empresa $empresa, string $telefone, string $mensagem): bool
    {
        if (!$empresa->whatsapp_instance || !$empresa->whatsapp_api_token) {
            Log::warning("[Z-API] Configuração incompleta para empresa {$empresa->id}");
            return false;
        }

        $base = $empresa->whatsapp_api_url ?: 'https://api.z-api.io';

        try {
            $response = Http::withHeaders([
                'Client-Token' => $empresa->whatsapp_api_token,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(
                rtrim($base, '/')."/instances/{$empresa->whatsapp_instance}/token/{$empresa->whatsapp_api_token}/send-text",
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

    public function testar(Empresa $empresa, string $telefoneDestino): array
    {
        $ok = $this->enviar($empresa, $telefoneDestino, "[Teste de conexão WhatsApp via Z-API - {$empresa->nome}]");
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
