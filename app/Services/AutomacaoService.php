<?php

namespace App\Services;

use App\Models\Automacao;
use App\Models\AutomacaoLog;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Resgate;
use Illuminate\Support\Collection;

class AutomacaoService
{
    public function __construct(protected WhatsappService $whatsapp) {}

    /**
     * Executa todas as automações ativas de todas as empresas.
     * Chamado pelo command `automacoes:executar` (diariamente).
     */
    public function executarTodas(): array
    {
        $resumo = ['enviados' => 0, 'falhas' => 0, 'automacoes' => 0];

        Automacao::where('ativo', true)->get()->each(function (Automacao $auto) use (&$resumo) {
            // Personalizadas com gatilho 'manual' não rodam em batch — só
            // pelo botão "Executar agora".
            if ($auto->personalizada && $auto->gatilho === 'manual') return;

            $clientes = $this->buscarClientesAlvo($auto);
            if ($clientes->isEmpty()) return;

            foreach ($clientes as $cliente) {
                if ($this->jaEnviadoParaCliente($auto, $cliente)) continue;

                $sucesso = $this->enviarMensagemAutomacao($auto, $cliente);
                $sucesso ? $resumo['enviados']++ : $resumo['falhas']++;
                if ($sucesso) $auto->increment('total_enviados');
            }

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
        // Pra gatilho manual, ignora gatilhos automáticos: dispara
        // pra todos os clientes ativos com whatsapp.
        $clientes = $auto->personalizada && $auto->gatilho === 'manual'
            ? Cliente::where('ativo', true)->where('aceita_whatsapp', true)->get()
            : $this->buscarClientesAlvo($auto);

        $enviados = 0; $falhas = 0;

        foreach ($clientes as $cliente) {
            $sucesso = $this->enviarMensagemAutomacao($auto, $cliente);
            $sucesso ? $enviados++ : $falhas++;
            if ($sucesso) $auto->increment('total_enviados');
        }

        $auto->update(['ultima_execucao' => now()]);
        return ['enviados' => $enviados, 'falhas' => $falhas, 'total' => $clientes->count()];
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
        // O lockForUpdate de Cliente serializa as 2 transactions mas ambas
        // chamam disparar() → 2 WhatsApp "obrigado pela compra" pro mesmo
        // cliente. Janela curta (2min) bloqueia spam de duplo clique sem
        // impedir eventos legítimos repetidos (cliente faz outra compra
        // 3min depois recebe normalmente). boas_vindas tem janela maior
        // (1 dia) pq tecnicamente só roda 1x na vida do cliente.
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

    protected function buscarClientesAlvo(Automacao $auto): Collection
    {
        // Automações são globais — busca em todas empresas
        $base = Cliente::where('ativo', true)
            ->where('aceita_whatsapp', true);

        if ($auto->personalizada) {
            return $this->buscarPorGatilho($auto, $base);
        }

        return match ($auto->tipo) {
            'aniversario' => $base->whereMonth('data_nascimento', now()->month)
                ->whereDay('data_nascimento', now()->day)->get(),

            'pontos_vencendo' => $base->where('pontos_atual', '>', 0)
                ->whereHas('transacoesPontos', function ($q) use ($auto) {
                    $q->where('tipo', 'credito')
                      ->whereNotNull('expira_em')
                      ->whereBetween('expira_em', [now(), now()->addDays(max($auto->dias_offset, 7))]);
                })->get(),

            'inativo_30d' => $base->where(function ($q) {
                $q->where('ultima_compra', '<', now()->subDays(30))
                  ->where('ultima_compra', '>=', now()->subDays(31));
            })->get(),

            'inativo_60d' => $base->where(function ($q) {
                $q->where('ultima_compra', '<', now()->subDays(60))
                  ->where('ultima_compra', '>=', now()->subDays(61));
            })->get(),

            // Tipos de evento individual: não rodam em batch
            default => collect(),
        };
    }

    protected function buscarPorGatilho(Automacao $auto, $base): Collection
    {
        return match ($auto->gatilho) {
            'manual' => collect(),

            'inativo_dias' => $base->whereNotNull('ultima_compra')
                ->where('ultima_compra', '<=', now()->subDays((int) ($auto->dias_offset ?: 30)))->get(),

            'compras_total' => $base->where('total_compras', '>=', (int) ($auto->valor_referencia ?: 1))->get(),

            'gasto_total' => $base->where('total_gasto', '>=', (float) ($auto->valor_referencia ?: 0))->get(),

            'cadastro_offset' => $base->whereDate('created_at', '=',
                now()->subDays((int) ($auto->dias_offset ?: 7))->toDateString())->get(),

            'pontos_acumulados' => $base->where('pontos_atual', '>=', (float) ($auto->valor_referencia ?: 0))->get(),

            default => collect(),
        };
    }

    /**
     * Decide se já foi enviado pra esse cliente. Pra gatilhos de estado
     * permanente (atingiu N compras, X pontos, etc.), checa o histórico
     * inteiro. Pros demais, só evita repetição no mesmo dia.
     */
    protected function jaEnviadoParaCliente(Automacao $auto, Cliente $cliente): bool
    {
        $unicaVez = $auto->personalizada && in_array($auto->gatilho, Automacao::GATILHOS_UNICA_VEZ);

        $query = AutomacaoLog::where('automacao_id', $auto->id)
            ->where('cliente_id', $cliente->id);

        if ($unicaVez) {
            return $query->where('sucesso', true)->exists();
        }

        return $query->whereDate('created_at', today())->exists();
    }

    protected function jaEnviadoHoje(Automacao $auto, Cliente $cliente): bool
    {
        return AutomacaoLog::where('automacao_id', $auto->id)
            ->where('cliente_id', $cliente->id)
            ->whereDate('created_at', today())
            ->exists();
    }
}
