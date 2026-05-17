<?php

namespace App\Services\Whatsapp;

use App\Models\ConfiguracaoSistema;
use App\Support\LogScrubber;
use Illuminate\Support\Facades\Log;

/**
 * Driver mock pra dev/testes. Pode cair aqui em PRODUÇÃO quando
 * whatsapp_ativo=false ou provider desconhecido — por isso os logs
 * passam por LogScrubber igual aos drivers reais.
 */
class MockDriver implements WhatsappDriverInterface
{
    public function enviar(ConfiguracaoSistema $config, string $telefone, string $mensagem): array
    {
        Log::info('[WhatsApp MOCK] → '.LogScrubber::scrub($telefone).': '.LogScrubber::scrub($mensagem));
        return ['ok' => true, 'external_id' => 'mock_'.bin2hex(random_bytes(6)), 'erro' => null];
    }

    public function testar(ConfiguracaoSistema $config, string $telefoneDestino): array
    {
        $this->enviar($config, $telefoneDestino, "[Teste de conexão WhatsApp - {$config->nome_sistema}]");
        return ['ok' => true, 'mensagem' => 'Modo MOCK ativo: mensagem registrada em storage/logs/laravel.log'];
    }

    public function enviarComBotoes(ConfiguracaoSistema $config, string $telefone, string $mensagem, array $botoes): array
    {
        $rotulos = implode(' | ', array_map(fn($b) => "[{$b['type']}:{$b['label']}={$b['value']}]", $botoes));
        Log::info('[WhatsApp MOCK] → '.LogScrubber::scrub($telefone).': '.LogScrubber::scrub($mensagem).'  botões: '.$rotulos);
        return ['ok' => true, 'external_id' => 'mock_'.bin2hex(random_bytes(6)), 'erro' => null];
    }
}
