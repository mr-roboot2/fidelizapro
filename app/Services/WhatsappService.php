<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CampanhaEnvio;
use App\Models\Cliente;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use App\Models\WhatsappTemplate;
use App\Services\Whatsapp\EvolutionDriver;
use App\Services\Whatsapp\MetaCloudDriver;
use App\Services\Whatsapp\MockDriver;
use App\Services\Whatsapp\WhatsappDriverInterface;
use App\Services\Whatsapp\ZapiDriver;

class WhatsappService
{
    /**
     * Resolve o driver baseado na configuração GLOBAL do sistema (super admin).
     * Cai em mock se desativado/inválido.
     */
    public function driver(): WhatsappDriverInterface
    {
        $config = ConfiguracaoSistema::instancia();

        if (!$config->whatsapp_ativo) {
            return new MockDriver();
        }

        return match ($config->whatsapp_provider) {
            'evolution' => new EvolutionDriver(),
            'zapi'      => new ZapiDriver(),
            'meta_cloud'=> new MetaCloudDriver(),
            default     => new MockDriver(),
        };
    }

    public function config(): ConfiguracaoSistema
    {
        return ConfiguracaoSistema::instancia();
    }

    public function enviar(Empresa $empresa, string $telefone, string $mensagem): bool
    {
        return $this->driver()->enviar($this->config(), $telefone, $mensagem);
    }

    /**
     * Envia mensagem por evento (otp, aniversario, boas_vindas, etc.).
     *
     * Se a empresa tem template aprovado configurado para esse evento e o
     * provedor é meta_cloud, usa template (chega sempre, mesmo fora da
     * janela de 24h). Senão, faz fallback pro texto livre fornecido em
     * $textoFallback (ou monta a partir do exemplo do evento).
     */
    public function enviarEvento(Empresa $empresa, string $telefone, string $evento, array $parametros = [], ?string $textoFallback = null): bool
    {
        $config = $this->config();
        $driver = $this->driver();

        if ($config->whatsapp_provider === 'meta_cloud' && $config->whatsapp_ativo) {
            $tpl = WhatsappTemplate::where('evento', $evento)
                ->where('ativo', true)
                ->first();

            if ($tpl && $driver instanceof MetaCloudDriver) {
                return $driver->enviarTemplate($config, $telefone, $tpl->nome_template, $tpl->idioma, $parametros);
            }
        }

        if ($textoFallback === null) {
            $def = WhatsappTemplate::EVENTOS[$evento] ?? null;
            $textoFallback = $def['exemplo'] ?? '';
            $i = 1;
            foreach ($parametros as $valor) {
                $textoFallback = str_replace("{{{$i}}}", (string) $valor, $textoFallback);
                $i++;
            }
        }

        return $driver->enviar($config, $telefone, $textoFallback);
    }

    public function testar(string $telefoneDestino): array
    {
        return $this->driver()->testar($this->config(), $telefoneDestino);
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
