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
            $clientes = $this->buscarClientesAlvo($auto);
            if ($clientes->isEmpty()) return;

            foreach ($clientes as $cliente) {
                if ($this->jaEnviadoHoje($auto, $cliente)) continue;

                $msg = $this->whatsapp->personalizarMensagem($auto->mensagem, $cliente);
                $sucesso = $this->whatsapp->enviar($auto->empresa, $cliente->telefone, $msg);

                AutomacaoLog::create([
                    'automacao_id' => $auto->id,
                    'cliente_id' => $cliente->id,
                    'sucesso' => $sucesso,
                    'mensagem_enviada' => $msg,
                    'erro' => $sucesso ? null : 'Falha no envio',
                ]);

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
        $clientes = $this->buscarClientesAlvo($auto);
        $enviados = 0; $falhas = 0;

        foreach ($clientes as $cliente) {
            $msg = $this->whatsapp->personalizarMensagem($auto->mensagem, $cliente);
            $sucesso = $this->whatsapp->enviar($auto->empresa, $cliente->telefone, $msg);

            AutomacaoLog::create([
                'automacao_id' => $auto->id,
                'cliente_id' => $cliente->id,
                'sucesso' => $sucesso,
                'mensagem_enviada' => $msg,
                'erro' => $sucesso ? null : 'Falha no envio',
            ]);

            $sucesso ? $enviados++ : $falhas++;
            if ($sucesso) $auto->increment('total_enviados');
        }

        $auto->update(['ultima_execucao' => now()]);
        return ['enviados' => $enviados, 'falhas' => $falhas, 'total' => $clientes->count()];
    }

    /**
     * Dispara automação específica de evento individual (boas_vindas, pos_compra, agradecimento_resgate).
     * Chamado pelos controllers que registram esses eventos.
     */
    public function disparar(Empresa $empresa, string $tipo, Cliente $cliente, array $extras = []): bool
    {
        $auto = Automacao::where('empresa_id', $empresa->id)
            ->where('tipo', $tipo)
            ->where('ativo', true)
            ->first();
        if (!$auto) return false;

        if ($this->jaEnviadoHoje($auto, $cliente)) return false;

        $msg = $this->whatsapp->personalizarMensagem($auto->mensagem, $cliente, $extras);
        $sucesso = $this->whatsapp->enviar($empresa, $cliente->telefone, $msg);

        AutomacaoLog::create([
            'automacao_id' => $auto->id,
            'cliente_id' => $cliente->id,
            'sucesso' => $sucesso,
            'mensagem_enviada' => $msg,
            'erro' => $sucesso ? null : 'Falha no envio',
        ]);

        if ($sucesso) {
            $auto->increment('total_enviados');
            $auto->update(['ultima_execucao' => now()]);
        }

        return $sucesso;
    }

    protected function buscarClientesAlvo(Automacao $auto): Collection
    {
        $base = Cliente::where('empresa_id', $auto->empresa_id)
            ->where('ativo', true)
            ->where('aceita_whatsapp', true);

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

    protected function jaEnviadoHoje(Automacao $auto, Cliente $cliente): bool
    {
        return AutomacaoLog::where('automacao_id', $auto->id)
            ->where('cliente_id', $cliente->id)
            ->whereDate('created_at', today())
            ->exists();
    }
}
