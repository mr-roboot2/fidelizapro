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
}
