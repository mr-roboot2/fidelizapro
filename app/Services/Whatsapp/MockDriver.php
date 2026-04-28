<?php

namespace App\Services\Whatsapp;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

class MockDriver implements WhatsappDriverInterface
{
    public function enviar(Empresa $empresa, string $telefone, string $mensagem): bool
    {
        Log::info("[WhatsApp MOCK][{$empresa->slug}] → {$telefone}: {$mensagem}");
        return true;
    }

    public function testar(Empresa $empresa, string $telefoneDestino): array
    {
        $this->enviar($empresa, $telefoneDestino, "[Teste de conexão WhatsApp - {$empresa->nome}]");
        return ['ok' => true, 'mensagem' => 'Modo MOCK ativo: mensagem registrada em storage/logs/laravel.log'];
    }
}
