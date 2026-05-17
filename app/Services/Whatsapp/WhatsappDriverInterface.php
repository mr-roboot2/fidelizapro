<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;

/**
 * Interface dos drivers WhatsApp.
 *
 * Retorno de `enviar()` e `enviarComBotoes()` é um array:
 *   ['ok' => bool, 'external_id' => ?string, 'erro' => ?string]
 *
 * external_id é o ID da mensagem no provider (Meta wamid, Z-API
 * messageId, Evolution key.id) — usado pelo webhook de status pra
 * correlacionar `sent/delivered/read/failed` com a linha em
 * `whatsapp_envios`. Mantido null quando o provider não fornece.
 */
interface WhatsappDriverInterface
{
    /**
     * Envia mensagem de texto.
     * @return array{ok: bool, external_id: ?string, erro: ?string}
     */
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): array;

    /**
     * Testa a conexão (envia mensagem de teste).
     * @return array{ok: bool, mensagem: string}
     */
    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array;

    /**
     * Envia mensagem com botões (COPY/URL/CALL/REPLY). Drivers que não
     * suportam fazem fallback pra texto.
     * @return array{ok: bool, external_id: ?string, erro: ?string}
     */
    public function enviarComBotoes(ConfiguracaoSistema $config, string $telefone, string $mensagem, array $botoes): array;
}
