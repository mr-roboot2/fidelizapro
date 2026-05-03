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
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): bool
    {
        if (!$config->whatsapp_api_url || !$config->whatsapp_api_token || !$config->whatsapp_instance) {
            Log::warning("[Evolution] Configuração global incompleta");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $config->whatsapp_api_token,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post(
                rtrim($config->whatsapp_api_url, '/').'/message/sendText/'.$config->whatsapp_instance,
                [
                    'number' => $this->normalizar($telefone),
                    'text' => $mensagem,
                ]
            );

            if (!$response->successful()) {
                Log::warning("[Evolution] Falha enviando para {$telefone}: ".$response->body());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("[Evolution] Exceção: ".$e->getMessage());
            return false;
        }
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        $ok = $this->enviar($config, $telefoneDestino, "[Teste de conexão WhatsApp via Evolution - {$config->nome_sistema}]");
        return [
            'ok' => $ok,
            'mensagem' => $ok ? 'Mensagem de teste enviada com sucesso!' : 'Falha — confira URL, token e instance nos logs.',
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
