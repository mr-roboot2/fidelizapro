<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\Log;

class MockDriver implements WhatsappDriverInterface
{
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): bool
    {
        Log::info("[WhatsApp MOCK] → {$telefone}: {$mensagem}");
        return true;
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        $this->enviar($config, $telefoneDestino, "[Teste de conexão WhatsApp - {$config->nome_sistema}]");
        return ['ok' => true, 'mensagem' => 'Modo MOCK ativo: mensagem registrada em storage/logs/laravel.log'];
    }
}
