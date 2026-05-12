<?php

namespace App\Listeners;

use App\Models\CronExecucao;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Registra início e fim de comandos artisan monitorados. Pra evitar inflar
 * a tabela com cada `php artisan list/help`, filtra pelos comandos da const
 * CronExecucao::COMANDOS_MONITORADOS.
 *
 * Uma execução cria um registro 'rodando' no Starting e atualiza pra
 * sucesso/falhou no Finished. O id da execução fica em memória estática.
 */
class RegistrarCronExecucao
{
    private static array $execucoesAtivas = [];

    public function handleStart(CommandStarting $event): void
    {
        $cmd = $event->command;
        if (!$cmd || !$this->monitorado($cmd)) return;

        try {
            $registro = CronExecucao::create([
                'comando'     => $cmd,
                'iniciado_em' => now(),
                'status'      => 'rodando',
                'origem'      => $this->origem(),
            ]);
            self::$execucoesAtivas[$cmd] = $registro->id;
        } catch (Throwable $e) {
            // Não bloqueia o comando se falhar ao logar
        }
    }

    public function handleFinish(CommandFinished $event): void
    {
        $cmd = $event->command;
        if (!$cmd || !$this->monitorado($cmd)) return;

        // Busca o registro mais recente em 'rodando' — fallback caso o static
        // perca estado (instâncias diferentes do listener no container).
        $registroId = self::$execucoesAtivas[$cmd] ?? CronExecucao::where('comando', $cmd)
            ->where('status', 'rodando')->latest('id')->value('id');
        unset(self::$execucoesAtivas[$cmd]);
        if (!$registroId) return;

        try {
            $registro = CronExecucao::find($registroId);
            if (!$registro) return;

            $terminoEm = now();
            $duracaoMs = (int) abs($registro->iniciado_em->diffInMilliseconds($terminoEm));
            $exitCode  = (int) $event->exitCode;

            $output = method_exists($event->output, 'fetch') ? $event->output->fetch() : '';

            $registro->update([
                'terminado_em' => $terminoEm,
                'duracao_ms'   => $duracaoMs,
                'status'       => $exitCode === 0 ? 'sucesso' : 'falhou',
                'exit_code'    => $exitCode,
                'output'       => $output ? mb_substr($output, 0, 4000) : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function monitorado(string $comando): bool
    {
        return array_key_exists($comando, CronExecucao::COMANDOS_MONITORADOS);
    }

    private function origem(): string
    {
        // PHP_SAPI 'cli' + presença de uma var define se é scheduler ou manual.
        // Quando o scheduler dispara, o ambiente é o mesmo do CLI — fica
        // difícil distinguir 100%. Marcamos 'cli' como default e o controller
        // que dispara 'Executar agora' vai marcar 'manual' diretamente.
        return defined('LARAVEL_START') ? 'cli' : 'scheduler';
    }
}
