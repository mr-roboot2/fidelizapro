<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limpa logs operacionais antigos pra evitar tabelas crescerem sem teto.
 * Compras, transações de pontos, movimentos de cashback e cobranças NÃO
 * entram aqui (são dados financeiros — retenção é decisão fiscal/legal).
 *
 * Retenção operacional (justificáveis pra suporte/debug):
 *   - otp_codigos:     7 dias  (já usado ou expirado)
 *   - whatsapp_envios: 90 dias (queixas de cliente sobre msg costumam ser <90d)
 *   - auditoria_logs:  180 dias (LGPD: justificável; trail por exportação se precisar mais)
 *   - cron_execucoes:  30 dias (apenas sucessos/falhas; rodando preservado)
 *   - automacao_logs:  90 dias (mesma lógica de whatsapp_envios)
 *
 * Use --dry-run pra ver quantas linhas seriam removidas sem deletar.
 */
class LimparLogsAntigos extends Command
{
    protected $signature = 'limpar:logs-antigos {--dry-run : Conta linhas sem deletar}';

    protected $description = 'Remove logs operacionais antigos (otp, whatsapp, auditoria, cron, automacao)';

    public function handle(): int
    {
        $alvos = [
            // [tabela, dias_retencao, coluna_data, where_extra (opcional)]
            ['otp_codigos',     7,   'created_at', "(usado = 1 OR expires_at < NOW())"],
            ['whatsapp_envios', 90,  'created_at', null],
            ['auditoria_logs',  180, 'created_at', null],
            ['cron_execucoes',  30,  'iniciado_em', "status IN ('sucesso','falhou')"],
            ['automacao_logs',  90,  'created_at', null],
        ];

        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach ($alvos as [$tabela, $dias, $coluna, $extra]) {
            if (!Schema::hasTable($tabela)) {
                $this->warn("Tabela {$tabela} não existe — pulando.");
                continue;
            }

            $query = DB::table($tabela)->where($coluna, '<', now()->subDays($dias));
            if ($extra) {
                $query->whereRaw($extra);
            }

            if ($dryRun) {
                $n = $query->count();
                $this->line("{$tabela}: {$n} linha(s) seriam removidas (>{$dias}d).");
            } else {
                $n = $query->delete();
                $this->info("{$tabela}: {$n} linha(s) removidas (>{$dias}d).");
            }
            $total += $n;
        }

        $verbo = $dryRun ? 'seriam removidas' : 'removidas';
        $this->line(PHP_EOL."Total: {$total} linha(s) {$verbo}.");
        return self::SUCCESS;
    }
}
