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
        $r = $this->driver()->enviar($this->config(), $telefone, $mensagem);
        $this->registrarEnvio($empresa, $telefone, $mensagem, $r, $evento, $origem);
        return $r['ok'];
    }

    /**
     * Envia mensagem com botões interativos. Cada botão é:
     *   ['type' => 'COPY'|'URL'|'CALL'|'REPLY', 'label' => string, 'value' => string]
     * Drivers que não suportam botões fazem fallback para texto puro.
     */
    public function enviarComBotoes(Empresa $empresa, string $telefone, string $mensagem, array $botoes, string $origem = 'sistema', string $evento = 'livre'): bool
    {
        $r = $this->driver()->enviarComBotoes($this->config(), $telefone, $mensagem, $botoes);
        $resumoBotoes = collect($botoes)->map(fn($b) => "[{$b['type']}:{$b['value']}]")->implode(' ');
        $this->registrarEnvio($empresa, $telefone, $mensagem.($resumoBotoes ? "\n".$resumoBotoes : ''), $r, $evento, $origem);
        return $r['ok'];
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
                $r = $driver->enviarTemplate($config, $telefone, $tpl->nome_template, $tpl->idioma, $parametros);
                $this->registrarEnvio($empresa, $telefone, "[template:{$tpl->nome_template}] ".implode(' | ', $parametros), $r, $evento, $origem);
                return $r['ok'];
            }
        }

        if ($textoFallback === null) {
            // Prioridade: texto personalizado salvo no banco → exemplo da constante
            $tplCustom = WhatsappTemplate::where('evento', $evento)->first();
            $textoFallback = $tplCustom?->texto ?: (WhatsappTemplate::EVENTOS[$evento]['exemplo'] ?? '');
            $i = 1;
            foreach ($parametros as $valor) {
                $textoFallback = str_replace("{{{$i}}}", (string) $valor, $textoFallback);
                $i++;
            }
        }

        $r = $driver->enviar($config, $telefone, $textoFallback);
        $this->registrarEnvio($empresa, $telefone, $textoFallback, $r, $evento, $origem);
        return $r['ok'];
    }

    /**
     * Persiste o envio na tabela whatsapp_envios. Falhas de log nunca
     * propagam — mensagem já foi enviada (ou tentativa já foi feita) e o
     * fluxo principal não deve cair por causa do registro.
     */
    /**
     * @param array{ok: bool, external_id: ?string, erro: ?string} $r
     */
    protected function registrarEnvio(?Empresa $empresa, string $telefone, ?string $mensagem, array $r, string $evento, string $origem): void
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
                'sucesso'    => (bool) $r['ok'],
                // external_id persistido pra correlação com webhook de status
                // (delivered/read/failed do Meta/Z-API/Evolution).
                'external_id'=> $r['external_id'] ?? null,
                'erro'       => $r['ok'] ? null : ($r['erro'] ?? 'Falha no envio'),
            ]);
        } catch (Throwable $e) {
            // Não derruba o fluxo principal, mas reporta pro logger
            // global pra não perder observability — sem isso, mensagem
            // entregue ao cliente mas registro de envio sumia. Relatórios
            // de KPI ficavam subnotificados.
            report($e);
        }
    }

    public function testar(string $telefoneDestino): array
    {
        return $this->driver()->testar($this->config(), $telefoneDestino);
    }

    /**
     * Dispara campanha em background via Job (queue). O super admin
     * recebe response imediata; o envio acontece em
     * `App\Jobs\EnviarCampanha`. Quando QUEUE_CONNECTION=sync (dev), o
     * dispatch executa em-linha — mesmo comportamento de antes.
     *
     * O status já foi setado pra 'enviando' pelo CampanhaController
     * antes do dispatch (lock atômico contra duplo-clique). O job
     * transiciona pra 'concluida' ao terminar, ou 'rascunho' se falhar.
     */
    public function dispararCampanha(Campanha $campanha): void
    {
        \App\Jobs\EnviarCampanha::dispatch($campanha);
    }

    /**
     * Método interno chamado pelo Job — faz o envio síncrono real
     * dentro do worker. Não chamar direto do request HTTP em campanhas
     * grandes (estoura max_execution_time).
     */
    public function dispararCampanhaImediato(Campanha $campanha): void
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
        // Campanha global (empresa_id = null) atinge clientes de todas empresas.
        // whereNotNull('telefone'): cliente sem telefone crashava drivers em
        // preg_replace('/\D/', '', null) (deprecated PHP 8.1+) e bate na
        // API do Meta com `to: ''` retornando erro pouco descritivo.
        $query = Cliente::where('ativo', true)
            ->where('aceita_whatsapp', true)
            ->whereNotNull('telefone')
            ->where('telefone', '!=', '');

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
