<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Evolution API (open-source brasileira, mais usada).
 * Docs: https://doc.evolution-api.com/
 */
class EvolutionDriver implements WhatsappDriverInterface
{
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): array
    {
        if (!$config->whatsapp_api_url || !$config->whatsapp_api_token || !$config->whatsapp_instance) {
            Log::warning("[Evolution] Configuração global incompleta");
            return ['ok' => false, 'external_id' => null, 'erro' => 'Config global incompleta'];
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $config->whatsapp_api_token,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(
                rtrim($config->whatsapp_api_url, '/').'/message/sendText/'.$config->whatsapp_instance,
                [
                    'number' => \App\Support\PhoneNormalizer::normalize($telefone),
                    'text' => $mensagem,
                ]
            );

            if (!$response->successful()) {
                Log::warning('[Evolution] Falha enviando mensagem', [
                    'tel'  => \App\Support\LogScrubber::scrub($telefone),
                    'body' => \App\Support\LogScrubber::scrub($response->body()),
                ]);
                return ['ok' => false, 'external_id' => null, 'erro' => 'HTTP '.$response->status()];
            }
            // Evolution retorna `key.id` no payload de sucesso
            return [
                'ok' => true,
                'external_id' => $response->json('key.id'),
                'erro' => null,
            ];
        } catch (\Throwable $e) {
            Log::error("[Evolution] Exceção: ".$e->getMessage());
            return ['ok' => false, 'external_id' => null, 'erro' => $e->getMessage()];
        }
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        $r = $this->enviar($config, $telefoneDestino, "[Teste de conexão WhatsApp via Evolution - {$config->nome_sistema}]");
        return [
            'ok' => $r['ok'],
            'mensagem' => $r['ok'] ? 'Mensagem de teste enviada com sucesso!' : 'Falha — confira URL, token e instance nos logs.',
        ];
    }

    /**
     * Evolution não tem endpoint padronizado pra botões — fallback pra texto
     * com o código/URL anexados.
     */
    public function enviarComBotoes(ConfiguracaoSistema $config, string $telefone, string $mensagem, array $botoes): array
    {
        return $this->enviar($config, $telefone, $this->fallbackTexto($mensagem, $botoes));
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
