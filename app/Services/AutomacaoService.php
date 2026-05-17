<?php

namespace App\Services;

use App\Models\Automacao;
use App\Models\AutomacaoLog;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;

class AutomacaoService
{
    /**
     * Tamanho do chunk em batch. 500 é equilíbrio entre roundtrips ao DB
     * (querys de bulk-fetch por chunk) e memory peak por iteração.
     */
    protected const CHUNK_SIZE = 500;

    public function __construct(protected WhatsappService $whatsapp) {}

    /**
     * Executa todas as automações ativas. Chamado pelo cron
     * `automacoes:executar` (diariamente). Antes carregava TODOS os
     * clientes elegíveis em memória + 1 query `jaEnviadoParaCliente`
     * por cliente — em base com 100k clientes ativos virava OOM.
     * Agora chunkById(500) + 1 query bulk de logs por chunk.
     */
    public function executarTodas(): array
    {
        $resumo = ['enviados' => 0, 'falhas' => 0, 'automacoes' => 0];

        Automacao::where('ativo', true)->get()->each(function (Automacao $auto) use (&$resumo) {
            // Personalizadas com gatilho 'manual' não rodam em batch — só
            // pelo botão "Executar agora".
            if ($auto->personalizada && $auto->gatilho === 'manual') return;

            $builder = $this->buscarClientesAlvoQuery($auto);
            if ($builder === null) return;

            $unicaVez = $auto->personalizada && in_array($auto->gatilho, Automacao::GATILHOS_UNICA_VEZ, true);

            // Eager load `empresa` pra evitar N+1 dentro do envio.
            $builder->with('empresa')->chunkById(self::CHUNK_SIZE, function ($chunk) use ($auto, $unicaVez, &$resumo) {
                $ids = $chunk->pluck('id')->all();

                // 1 query: ids de clientes do chunk que já receberam a automação.
                $logQuery = AutomacaoLog::where('automacao_id', $auto->id)
                    ->whereIn('cliente_id', $ids);

                $jaEnviados = $unicaVez
                    ? $logQuery->where('sucesso', true)->pluck('cliente_id')->all()
                    : $logQuery->whereDate('created_at', today())->pluck('cliente_id')->all();

                $jaSet = array_flip($jaEnviados);

                foreach ($chunk as $cliente) {
                    if (isset($jaSet[$cliente->id])) continue;

                    $sucesso = $this->enviarMensagemAutomacao($auto, $cliente);
                    $sucesso ? $resumo['enviados']++ : $resumo['falhas']++;
                    if ($sucesso) $auto->increment('total_enviados');
                }
            });

            $auto->update(['ultima_execucao' => now()]);
            $resumo['automacoes']++;
        });

        return $resumo;
    }

    /**
     * Executa apenas uma automação específica (forçado pelo admin via botão).
     */
    public function executarUma(Automacao $auto): array
    {
        $builder = ($auto->personalizada && $auto->gatilho === 'manual')
            ? Cliente::where('ativo', true)->where('aceita_whatsapp', true)->whereNotNull('telefone')
            : $this->buscarClientesAlvoQuery($auto);

        if ($builder === null) {
            return ['enviados' => 0, 'falhas' => 0, 'total' => 0];
        }

        $enviados = 0; $falhas = 0; $total = 0;

        $builder->with('empresa')->chunkById(self::CHUNK_SIZE, function ($chunk) use ($auto, &$enviados, &$falhas, &$total) {
            foreach ($chunk as $cliente) {
                $sucesso = $this->enviarMensagemAutomacao($auto, $cliente);
                $sucesso ? $enviados++ : $falhas++;
                if ($sucesso) $auto->increment('total_enviados');
                $total++;
            }
        });

        $auto->update(['ultima_execucao' => now()]);
        return ['enviados' => $enviados, 'falhas' => $falhas, 'total' => $total];
    }

    /**
     * Mapeia tipo da automação pra evento de template e seus parâmetros.
     * Retorna null pros tipos sem mapping (cai pro texto livre).
     */
    protected function eventoTemplateParaTipo(string $tipoAutomacao, Cliente $cliente, array $extras = []): ?array
    {
        $nome = $cliente->nome;
        $empresa = $cliente->empresa->nome ?? '';

        return match ($tipoAutomacao) {
            'aniversario'           => ['evento' => 'aniversario',     'params' => [$nome]],
            'pontos_vencendo'       => ['evento' => 'pontos_vencendo', 'params' => [$nome, (string) (int) $cliente->pontos_atual]],
            'inativo_30d'           => ['evento' => 'inativo_30d',     'params' => [$nome]],
            'inativo_60d'           => ['evento' => 'inativo_60d',     'params' => [$nome]],
            'boas_vindas'           => ['evento' => 'boas_vindas',     'params' => [$nome, $empresa]],
            'agradecimento_resgate' => ['evento' => 'resgate_aprovado','params' => [$nome, $extras['{recompensa}'] ?? '']],
            default                 => null,
        };
    }

    protected function enviarMensagemAutomacao(Automacao $auto, Cliente $cliente, array $extras = []): bool
    {
        $msg = $this->whatsapp->personalizarMensagem($auto->mensagem, $cliente, $extras);
        $mapping = $this->eventoTemplateParaTipo($auto->tipo, $cliente, $extras);

        // Automações são globais (sem empresa_id) — usa a empresa do cliente
        $empresa = $cliente->empresa;

        if ($mapping) {
            $sucesso = $this->whatsapp->enviarEvento(
                $empresa,
                $cliente->telefone,
                $mapping['evento'],
                $mapping['params'],
                $msg,
                'automacao'
            );
        } else {
            $sucesso = $this->whatsapp->enviar($empresa, $cliente->telefone, $msg, 'automacao', $auto->tipo);
        }

        AutomacaoLog::create([
            'automacao_id' => $auto->id,
            'cliente_id' => $cliente->id,
            'sucesso' => $sucesso,
            'mensagem_enviada' => $msg,
            'erro' => $sucesso ? null : 'Falha no envio',
        ]);

        return $sucesso;
    }

    /**
     * Dispara automação específica de evento individual (boas_vindas, pos_compra, agradecimento_resgate).
     * Chamado pelos controllers que registram esses eventos.
     */
    public function disparar(Empresa $empresa, string $tipo, Cliente $cliente, array $extras = []): bool
    {
        $auto = Automacao::where('tipo', $tipo)->where('ativo', true)->first();
        if (!$auto) return false;

        // Idempotência: duplo clique no caixa /admin/caixa/lancar resulta
        // em 2 chamadas a CompraService::registrar dentro do mesmo segundo.
        // Janela curta (2min) bloqueia spam sem impedir eventos legítimos
        // repetidos. boas_vindas tem janela 1 dia (só roda 1x na vida).
        $janela = $tipo === 'boas_vindas' ? now()->subDay() : now()->subMinutes(2);
        $jaDisparou = AutomacaoLog::where('automacao_id', $auto->id)
            ->where('cliente_id', $cliente->id)
            ->where('sucesso', true)
            ->where('created_at', '>=', $janela)
            ->exists();
        if ($jaDisparou) {
            return false;
        }

        $sucesso = $this->enviarMensagemAutomacao($auto, $cliente, $extras);

        if ($sucesso) {
            $auto->increment('total_enviados');
            $auto->update(['ultima_execucao' => now()]);
        }

        return $sucesso;
    }

    /**
     * Retorna Builder pronto pra chunkById (ou null se essa automação
     * não roda em batch). Antes retornava Collection pré-carregada —
     * causa raiz do OOM em base grande.
     */
    protected function buscarClientesAlvoQuery(Automacao $auto): ?Builder
    {
        // Automações são globais — busca em todas empresas.
        // whereNotNull(telefone): cliente sem telefone crashava drivers.
        $base = Cliente::where('ativo', true)
            ->where('aceita_whatsapp', true)
            ->whereNotNull('telefone')
            ->where('telefone', '!=', '');

        if ($auto->personalizada) {
            return $this->buscarPorGatilhoQuery($auto, $base);
        }

        return match ($auto->tipo) {
            'aniversario' => $base->whereMonth('data_nascimento', now()->month)
                ->whereDay('data_nascimento', now()->day),

            'pontos_vencendo' => $base->where('pontos_atual', '>', 0)
                ->whereHas('transacoesPontos', function ($q) use ($auto) {
                    $q->where('tipo', 'credito')
                      ->whereNotNull('expira_em')
                      ->whereBetween('expira_em', [now(), now()->addDays(max((int) $auto->dias_offset, 7))]);
                }),

            'inativo_30d' => $base->where('ultima_compra', '<', now()->subDays(30))
                ->where('ultima_compra', '>=', now()->subDays(31)),

            'inativo_60d' => $base->where('ultima_compra', '<', now()->subDays(60))
                ->where('ultima_compra', '>=', now()->subDays(61)),

            // Tipos de evento individual: não rodam em batch
            default => null,
        };
    }

    protected function buscarPorGatilhoQuery(Automacao $auto, Builder $base): ?Builder
    {
        return match ($auto->gatilho) {
            'manual' => null,

            'inativo_dias' => $base->whereNotNull('ultima_compra')
                ->where('ultima_compra', '<=', now()->subDays((int) ($auto->dias_offset ?: 30))),

            'compras_total' => $base->where('total_compras', '>=', (int) ($auto->valor_referencia ?: 1)),

            'gasto_total' => $base->where('total_gasto', '>=', (float) ($auto->valor_referencia ?: 0)),

            'cadastro_offset' => $base->whereDate('created_at', '=',
                now()->subDays((int) ($auto->dias_offset ?: 7))->toDateString()),

            'pontos_acumulados' => $base->where('pontos_atual', '>=', (float) ($auto->valor_referencia ?: 0)),

            default => null,
        };
    }
}
