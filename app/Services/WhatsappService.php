<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CampanhaEnvio;
use App\Models\Cliente;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use App\Models\WhatsappEnvio;
use App\Models\WhatsappTemplate;
use App\Services\Whatsapp\EvolutionDriver;
use App\Services\Whatsapp\MetaCloudDriver;
use App\Services\Whatsapp\MockDriver;
use App\Services\Whatsapp\WhatsappDriverInterface;
use App\Services\Whatsapp\ZapiDriver;
use Throwable;

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

    public function enviar(Empresa $empresa, string $telefone, string $mensagem, string $origem = 'manual', string $evento = 'livre'): bool
    {
        $sucesso = $this->driver()->enviar($this->config(), $telefone, $mensagem);
        $this->registrarEnvio($empresa, $telefone, $mensagem, $sucesso, $evento, $origem);
        return $sucesso;
    }

    /**
     * Envia mensagem por evento (otp, aniversario, boas_vindas, etc.).
     *
     * Se a empresa tem template aprovado configurado para esse evento e o
     * provedor é meta_cloud, usa template (chega sempre, mesmo fora da
     * janela de 24h). Senão, faz fallback pro texto livre fornecido em
     * $textoFallback (ou monta a partir do exemplo do evento).
     */
    public function enviarEvento(Empresa $empresa, string $telefone, string $evento, array $parametros = [], ?string $textoFallback = null, string $origem = 'sistema'): bool
    {
        $config = $this->config();
        $driver = $this->driver();
        $usouTemplate = false;

        if ($config->whatsapp_provider === 'meta_cloud' && $config->whatsapp_ativo) {
            $tpl = WhatsappTemplate::where('evento', $evento)
                ->where('ativo', true)
                ->first();

            if ($tpl && $driver instanceof MetaCloudDriver) {
                $sucesso = $driver->enviarTemplate($config, $telefone, $tpl->nome_template, $tpl->idioma, $parametros);
                $usouTemplate = true;
                $this->registrarEnvio($empresa, $telefone, "[template:{$tpl->nome_template}] ".implode(' | ', $parametros), $sucesso, $evento, $origem);
                return $sucesso;
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

        $sucesso = $driver->enviar($config, $telefone, $textoFallback);
        $this->registrarEnvio($empresa, $telefone, $textoFallback, $sucesso, $evento, $origem);
        return $sucesso;
    }

    /**
     * Persiste o envio na tabela whatsapp_envios. Falhas de log nunca
     * propagam — mensagem já foi enviada (ou tentativa já foi feita) e o
     * fluxo principal não deve cair por causa do registro.
     */
    protected function registrarEnvio(?Empresa $empresa, string $telefone, ?string $mensagem, bool $sucesso, string $evento, string $origem): void
    {
        try {
            $config = $this->config();
            $clienteId = null;
            if ($empresa) {
                $clienteId = Cliente::where('empresa_id', $empresa->id)
                    ->whereTelefone($telefone)
                    ->value('id');
            }

            WhatsappEnvio::create([
                'empresa_id' => $empresa?->id,
                'cliente_id' => $clienteId,
                'telefone'   => $telefone,
                'evento'     => $evento,
                'origem'     => $origem,
                'mensagem'   => $mensagem,
                'provider'   => $config->whatsapp_ativo ? $config->whatsapp_provider : 'mock',
                'sucesso'    => $sucesso,
                'erro'       => $sucesso ? null : 'Falha no envio (verifique log do driver)',
            ]);
        } catch (Throwable $e) {
            // ignora silenciosamente — não pode derrubar o fluxo principal
        }
    }

    public function testar(string $telefoneDestino): array
    {
        return $this->driver()->testar($this->config(), $telefoneDestino);
    }

    public function dispararCampanha(Campanha $campanha): void
    {
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
            // Empresa é só pra contexto/log — config real é global
            $sucesso = $this->enviar($cliente->empresa, $cliente->telefone, $msg, 'campanha', 'campanha');

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
        // Campanha global (empresa_id = null) atinge clientes de todas empresas
        $query = Cliente::where('ativo', true)
            ->where('aceita_whatsapp', true);

        if ($campanha->empresa_id) {
            $query->where('empresa_id', $campanha->empresa_id);
        }

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
