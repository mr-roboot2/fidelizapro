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
                Log::warning('[Z-API] Falha enviando mensagem', [
                    'tel'  => \App\Support\LogScrubber::scrub($telefone),
                    'body' => \App\Support\LogScrubber::scrub($response->body()),
                ]);
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

    /**
     * Z-API: /send-button-actions — botões interativos com ações:
     * COPY (copia código), URL (abre link), CALL (liga). Limite Z-API: 3 botões.
     */
    public function enviarComBotoes(ConfiguracaoSistema $config, string $telefone, string $mensagem, array $botoes): bool
    {
        if (!$config->whatsapp_instance || !$config->whatsapp_api_token) {
            Log::warning("[Z-API] Configuração global incompleta (botões)");
            return false;
        }
        if (empty($botoes)) {
            return $this->enviar($config, $telefone, $mensagem);
        }

        $base = $config->whatsapp_api_url ?: 'https://api.z-api.io';
        $clientToken = $config->whatsapp_client_token ?: $config->whatsapp_api_token;

        // Mapeia o formato genérico pro formato Z-API
        $buttonActions = [];
        foreach (array_slice($botoes, 0, 3) as $i => $b) {
            $tipo  = strtoupper($b['type'] ?? 'REPLY');
            $label = (string) ($b['label'] ?? 'Ok');
            $value = (string) ($b['value'] ?? '');
            $entry = ['id' => (string) ($i + 1), 'type' => $tipo, 'label' => $label];
            switch ($tipo) {
                case 'COPY': $entry['copyCode'] = $value; break;
                case 'URL':  $entry['url']      = $value; break;
                case 'CALL': $entry['phone']    = $value; break;
                case 'REPLY': default: break;
            }
            $buttonActions[] = $entry;
        }

        try {
            $response = Http::withHeaders([
                'Client-Token' => $clientToken,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(
                rtrim($base, '/')."/instances/{$config->whatsapp_instance}/token/{$config->whatsapp_api_token}/send-button-actions",
                [
                    'phone'         => $this->normalizar($telefone),
                    'message'       => $mensagem,
                    'buttonActions' => $buttonActions,
                ]
            );

            if (!$response->successful()) {
                Log::warning('[Z-API] Falha enviando botões', [
                    'tel'  => \App\Support\LogScrubber::scrub($telefone),
                    'body' => \App\Support\LogScrubber::scrub($response->body()),
                ]);
                // Fallback: texto puro com os valores dos botões anexados
                return $this->enviar($config, $telefone, $this->fallbackTexto($mensagem, $botoes));
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("[Z-API] Exceção (botões): ".$e->getMessage());
            return $this->enviar($config, $telefone, $this->fallbackTexto($mensagem, $botoes));
        }
    }

    protected function fallbackTexto(string $mensagem, array $botoes): string
    {
        $partes = [$mensagem];
        foreach ($botoes as $b) {
            $tipo  = strtoupper($b['type'] ?? '');
            $label = $b['label'] ?? '';
            $value = $b['value'] ?? '';
            $partes[] = match ($tipo) {
                'COPY' => "Código: *{$value}*",
                'URL'  => "{$label}: {$value}",
                'CALL' => "{$label}: {$value}",
                default => '',
            };
        }
        return implode("\n\n", array_filter($partes));
    }

    protected function normalizar(string $telefone): string
    {
        $apenas = preg_replace('/\D/', '', $telefone);
        if (strlen($apenas) === 11) $apenas = '55'.$apenas;
        if (strlen($apenas) === 10) $apenas = '55'.$apenas;
        return $apenas;
    }
}
