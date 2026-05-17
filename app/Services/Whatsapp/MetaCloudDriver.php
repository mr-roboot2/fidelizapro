<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API (oficial Meta).
 * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * Usa a configuração global do sistema (super admin) — uma WABA pra
 * todas as empresas.
 */
class MetaCloudDriver implements WhatsappDriverInterface
{
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): array
    {
        if (!$config->whatsapp_api_token || !$config->whatsapp_phone_id) {
            Log::warning("[Meta Cloud] Configuração global incompleta");
            return ['ok' => false, 'external_id' => null, 'erro' => 'Config global incompleta'];
        }

        try {
            $response = Http::withToken($config->whatsapp_api_token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v18.0/{$config->whatsapp_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'   => \App\Support\PhoneNormalizer::normalize($telefone),
                    'type' => 'text',
                    'text' => ['body' => $mensagem],
                ]);

            if (!$response->successful()) {
                Log::warning('[Meta Cloud] Falha enviando mensagem', [
                    'tel'  => \App\Support\LogScrubber::scrub($telefone),
                    'body' => \App\Support\LogScrubber::scrub($response->body()),
                ]);
                return ['ok' => false, 'external_id' => null, 'erro' => $response->json('error.message') ?? 'HTTP '.$response->status()];
            }
            // wamid extraído pra correlação com webhook de status
            return ['ok' => true, 'external_id' => $response->json('messages.0.id'), 'erro' => null];
        } catch (\Throwable $e) {
            Log::error('[Meta Cloud] Exceção: '.$e->getMessage());
            return ['ok' => false, 'external_id' => null, 'erro' => $e->getMessage()];
        }
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        if (!$config->whatsapp_api_token || !$config->whatsapp_phone_id) {
            return ['ok' => false, 'mensagem' => 'Configure token e Phone Number ID antes de testar.'];
        }

        try {
            $response = Http::withToken($config->whatsapp_api_token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v18.0/{$config->whatsapp_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'   => \App\Support\PhoneNormalizer::normalize($telefoneDestino),
                    'type' => 'template',
                    'template' => [
                        'name'     => 'hello_world',
                        'language' => ['code' => 'en_US'],
                    ],
                ]);

            if ($response->successful()) {
                $msgId = $response->json('messages.0.id');
                Log::info("[Meta Cloud] Teste enviado", ['msg_id' => $msgId]);
                return [
                    'ok' => true,
                    'mensagem' => "Template 'hello_world' enviado! Se não chegar, verifique: (1) número está como tester no Meta Console, (2) WABA verificada, (3) número de envio registrado.",
                ];
            }

            $erro = $response->json('error.message') ?? \App\Support\LogScrubber::scrub($response->body());
            $codigo = $response->json('error.code');
            Log::warning('[Meta Cloud] Falha no teste', ['erro' => \App\Support\LogScrubber::scrub((string) $erro)]);

            $dica = match ((int) $codigo) {
                131030 => 'Número de destino não está na lista de testers no Meta Console.',
                133010 => 'Número de envio não foi registrado. Faça o /register com PIN.',
                132000 => 'Template não existe ou não foi aprovado.',
                190    => 'Token inválido ou expirado.',
                default => null,
            };

            return [
                'ok' => false,
                'mensagem' => "Falha: {$erro}".($dica ? " — {$dica}" : ''),
            ];
        } catch (\Throwable $e) {
            Log::error("[Meta Cloud] Exceção no teste: ".$e->getMessage());
            return ['ok' => false, 'mensagem' => 'Erro de conexão: '.$e->getMessage()];
        }
    }

    /**
     * Envia mensagem usando um template aprovado pela Meta.
     * Os parâmetros devem estar na mesma ordem dos {{1}}, {{2}}... do template.
     */
    public function enviarTemplate(ConfiguracaoSistema $config, string $telefone, string $nomeTemplate, string $idioma, array $parametros): array
    {
        if (!$config->whatsapp_api_token || !$config->whatsapp_phone_id) {
            Log::warning("[Meta Cloud] Config global incompleta");
            return ['ok' => false, 'external_id' => null, 'erro' => 'Config global incompleta'];
        }

        $components = [];
        if (!empty($parametros)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string) $v], array_values($parametros)),
            ];
        }

        try {
            $response = Http::withToken($config->whatsapp_api_token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v18.0/{$config->whatsapp_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'   => \App\Support\PhoneNormalizer::normalize($telefone),
                    'type' => 'template',
                    'template' => array_filter([
                        'name'       => $nomeTemplate,
                        'language'   => ['code' => $idioma],
                        'components' => $components ?: null,
                    ]),
                ]);

            if (!$response->successful()) {
                Log::warning('[Meta Cloud] Falha enviando template', [
                    'template' => $nomeTemplate,
                    'tel'      => \App\Support\LogScrubber::scrub($telefone),
                    'body'     => \App\Support\LogScrubber::scrub($response->body()),
                ]);
                return ['ok' => false, 'external_id' => null, 'erro' => $response->json('error.message') ?? 'HTTP '.$response->status()];
            }
            return ['ok' => true, 'external_id' => $response->json('messages.0.id'), 'erro' => null];
        } catch (\Throwable $e) {
            Log::error('[Meta Cloud] Exceção template: '.$e->getMessage());
            return ['ok' => false, 'external_id' => null, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Meta Cloud não envia botões fora de templates aprovados — fallback
     * pra texto puro com o código/URL anexado no corpo.
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
