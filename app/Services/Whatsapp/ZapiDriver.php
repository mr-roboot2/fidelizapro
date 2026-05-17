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
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): array
    {
        if (!$config->whatsapp_instance || !$config->whatsapp_api_token) {
            Log::warning("[Z-API] Configuração global incompleta");
            return ['ok' => false, 'external_id' => null, 'erro' => 'Config global incompleta'];
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
                    'phone' => \App\Support\PhoneNormalizer::normalize($telefone),
                    'message' => $mensagem,
                ]
            );

            if (!$response->successful()) {
                Log::warning('[Z-API] Falha enviando mensagem', [
                    'tel'  => \App\Support\LogScrubber::scrub($telefone),
                    'body' => \App\Support\LogScrubber::scrub($response->body()),
                ]);
                // Extrai message do JSON Z-API (formato: {"error":"...","value":"..."})
                // pra propagar erro real (instância desconectada, token errado, etc).
                $msg = $response->json('message') ?? $response->json('error') ?? $response->json('value') ?? mb_substr($response->body(), 0, 200);
                return ['ok' => false, 'external_id' => null, 'erro' => 'HTTP '.$response->status().': '.$msg];
            }
            // Z-API retorna messageId ou id no payload — varia por versão
            return [
                'ok' => true,
                'external_id' => $response->json('messageId') ?? $response->json('id'),
                'erro' => null,
            ];
        } catch (\Throwable $e) {
            Log::error("[Z-API] Exceção: ".$e->getMessage());
            return ['ok' => false, 'external_id' => null, 'erro' => $e->getMessage()];
        }
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        $r = $this->enviar($config, $telefoneDestino, "[Teste de conexão WhatsApp via Z-API - {$config->nome_sistema}]");
        if ($r['ok']) {
            return ['ok' => true, 'mensagem' => 'Mensagem de teste enviada!'];
        }
        // Mostra o erro real (HTTP code + corpo da resposta Z-API) direto
        // na tela pra o super não precisar abrir SSH e tailar log toda vez.
        $erro = $r['erro'] ?? 'erro desconhecido';
        return [
            'ok' => false,
            'mensagem' => "Falha ({$erro}). Verifique: 1) Instance ID e Token bater com o painel Z-API → Instâncias; 2) Client-Token vir de Painel Z-API → Segurança → Token da conta (NÃO confundir com token da instância); 3) Instância estar conectada (QR code escaneado).",
        ];
    }

    /**
     * Z-API: /send-button-actions — botões interativos com ações:
     * COPY (copia código), URL (abre link), CALL (liga). Limite Z-API: 3 botões.
     */
    public function enviarComBotoes(ConfiguracaoSistema $config, string $telefone, string $mensagem, array $botoes): array
    {
        if (!$config->whatsapp_instance || !$config->whatsapp_api_token) {
            Log::warning("[Z-API] Configuração global incompleta (botões)");
            return ['ok' => false, 'external_id' => null, 'erro' => 'Config global incompleta'];
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
                    'phone'         => \App\Support\PhoneNormalizer::normalize($telefone),
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
            return [
                'ok' => true,
                'external_id' => $response->json('messageId') ?? $response->json('id'),
                'erro' => null,
            ];
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

}
