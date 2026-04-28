<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CampanhaEnvio;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Services\Whatsapp\EvolutionDriver;
use App\Services\Whatsapp\MetaCloudDriver;
use App\Services\Whatsapp\MockDriver;
use App\Services\Whatsapp\WhatsappDriverInterface;
use App\Services\Whatsapp\ZapiDriver;

class WhatsappService
{
    /**
     * Resolve o driver da empresa. Cai em mock se desativado/inválido.
     */
    public function driverFor(Empresa $empresa): WhatsappDriverInterface
    {
        if (!$empresa->whatsapp_ativo) {
            return new MockDriver();
        }

        return match ($empresa->whatsapp_provider) {
            'evolution' => new EvolutionDriver(),
            'zapi' => new ZapiDriver(),
            'meta_cloud' => new MetaCloudDriver(),
            default => new MockDriver(),
        };
    }

    public function enviar(Empresa $empresa, string $telefone, string $mensagem): bool
    {
        return $this->driverFor($empresa)->enviar($empresa, $telefone, $mensagem);
    }

    public function testar(Empresa $empresa, string $telefoneDestino): array
    {
        return $this->driverFor($empresa)->testar($empresa, $telefoneDestino);
    }

    public function dispararCampanha(Campanha $campanha): void
    {
        $empresa = $campanha->empresa;
        $clientes = $this->buscarClientesPorSegmento($campanha);

        $campanha->update([
            'status' => 'enviando',
            'total_destinatarios' => $clientes->count(),
        ]);

        $enviados = 0;
        $falhas = 0;

        foreach ($clientes as $cliente) {
            $envio = CampanhaEnvio::create([
                'campanha_id' => $campanha->id,
                'cliente_id' => $cliente->id,
                'status' => 'pendente',
            ]);

            $msg = $this->personalizarMensagem($campanha->mensagem, $cliente);
            $sucesso = $this->enviar($empresa, $cliente->telefone, $msg);

            $envio->update([
                'status' => $sucesso ? 'enviado' : 'falhou',
                'enviado_em' => $sucesso ? now() : null,
                'erro' => $sucesso ? null : 'Falha no envio',
            ]);

            $sucesso ? $enviados++ : $falhas++;
        }

        $campanha->update([
            'status' => 'concluida',
            'enviada_em' => now(),
            'total_enviados' => $enviados,
            'total_falhas' => $falhas,
        ]);
    }

    public function buscarClientesPorSegmento(Campanha $campanha)
    {
        $query = Cliente::where('empresa_id', $campanha->empresa_id)
            ->where('ativo', true)
            ->where('aceita_whatsapp', true);

        return match ($campanha->segmento) {
            'aniversariantes' => $query->whereMonth('data_nascimento', now()->month)->get(),
            'inativos' => $query->where(function ($q) {
                $q->whereNull('ultima_compra')->orWhere('ultima_compra', '<', now()->subDays(60));
            })->get(),
            'vips' => $query->orderByDesc('total_gasto')->limit(50)->get(),
            'sem_compra_30d' => $query->where(function ($q) {
                $q->whereNull('ultima_compra')->orWhere('ultima_compra', '<', now()->subDays(30));
            })->get(),
            default => $query->get(),
        };
    }

    public function personalizarMensagem(string $template, Cliente $cliente, array $extras = []): string
    {
        $vars = array_merge([
            '{nome}' => $cliente->nome,
            '{primeiro_nome}' => explode(' ', $cliente->nome)[0],
            '{pontos}' => number_format($cliente->pontos_atual, 0, ',', '.'),
            '{cashback}' => 'R$ '.number_format($cliente->cashback_atual, 2, ',', '.'),
            '{empresa}' => $cliente->empresa->nome,
        ], $extras);

        return strtr($template, $vars);
    }
}
