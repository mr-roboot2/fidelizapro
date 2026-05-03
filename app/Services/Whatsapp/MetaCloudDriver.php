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
        if (!$empresa->whatsapp_api_token || !$empresa->whatsapp_phone_id) {
            return ['ok' => false, 'mensagem' => 'Configure token e Phone Number ID antes de testar.'];
        }

        // Cloud API só permite texto livre dentro da janela de 24h após o
        // cliente ter respondido. Pra teste de conexão usamos o template
        // "hello_world" que vem aprovado por padrão em toda WABA.
        try {
            $response = Http::withToken($empresa->whatsapp_api_token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v18.0/{$empresa->whatsapp_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'   => $this->normalizar($telefoneDestino),
                    'type' => 'template',
                    'template' => [
                        'name'     => 'hello_world',
                        'language' => ['code' => 'en_US'],
                    ],
                ]);

            if ($response->successful()) {
                $msgId = $response->json('messages.0.id');
                Log::info("[Meta Cloud] Teste enviado", ['empresa' => $empresa->id, 'msg_id' => $msgId]);
                return [
                    'ok' => true,
                    'mensagem' => "Template 'hello_world' enviado! Se não chegar, verifique: (1) número está como tester no Meta Console, (2) WABA verificada, (3) número de envio registrado.",
                ];
            }

            $erro = $response->json('error.message') ?? $response->body();
            $codigo = $response->json('error.code');
            Log::warning("[Meta Cloud] Falha no teste", ['empresa' => $empresa->id, 'erro' => $erro]);

            // Mensagens de erro mais comuns traduzidas
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
    public function enviarTemplate(Empresa $empresa, string $telefone, string $nomeTemplate, string $idioma, array $parametros): bool
    {
        if (!$empresa->whatsapp_api_token || !$empresa->whatsapp_phone_id) {
            Log::warning("[Meta Cloud] Config incompleta empresa {$empresa->id}");
            return false;
        }

        $components = [];
        if (!empty($parametros)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string) $v], array_values($parametros)),
            ];
        }

        try {
            $response = Http::withToken($empresa->whatsapp_api_token)
                ->timeout(15)
                ->post("https://graph.facebook.com/v18.0/{$empresa->whatsapp_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'   => $this->normalizar($telefone),
                    'type' => 'template',
                    'template' => array_filter([
                        'name'       => $nomeTemplate,
                        'language'   => ['code' => $idioma],
                        'components' => $components ?: null,
                    ]),
                ]);

            if (!$response->successful()) {
                Log::warning("[Meta Cloud] Falha enviando template {$nomeTemplate} para {$telefone}: ".$response->body());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("[Meta Cloud] Exceção template: ".$e->getMessage());
            return false;
        }
    }

    protected function normalizar(string $telefone): string
    {
        $apenas = preg_replace('/\D/', '', $telefone);
        if (strlen($apenas) === 11) $apenas = '55'.$apenas;
        if (strlen($apenas) === 10) $apenas = '55'.$apenas;
        return $apenas;
    }
}
