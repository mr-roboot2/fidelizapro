<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;

interface WhatsappDriverInterface
{
    /**
     * Envia uma mensagem de texto via WhatsApp.
     * Retorna true se enviou com sucesso, false em caso de falha.
     */
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): bool;

    /**
     * Testa a conexão com o provider (envia mensagem de teste).
     * Retorna ['ok' => bool, 'mensagem' => string].
     */
    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array;

    /**
     * Envia mensagem com botões de ação (URL, COPY, CALL, REPLY).
     * Quando o provider não suporta, faz fallback pra texto puro com o
     * código/URL embutido no corpo da mensagem.
     *
     * Cada botão é um array com:
     *   ['type' => 'COPY'|'URL'|'CALL'|'REPLY', 'label' => string,
     *    'value' => string]   // codigo a copiar / url / telefone / id de resposta
     */
    public function enviarComBotoes(ConfiguracaoSistema $config, string $telefone, string $mensagem, array $botoes): bool;
}
