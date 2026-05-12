<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CronExecucao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class CronController extends Controller
{
    public function index()
    {
        $comandos = collect(CronExecucao::COMANDOS_MONITORADOS);

        // Última execução por comando
        $ultimas = $comandos->mapWithKeys(function ($_, $cmd) {
            return [$cmd => CronExecucao::where('comando', $cmd)->latest('iniciado_em')->first()];
        });

        // Estatísticas por comando (últimos 30 dias)
        $estatisticas = $comandos->mapWithKeys(function ($_, $cmd) {
            $base = CronExecucao::where('comando', $cmd)->where('iniciado_em', '>=', now()->subDays(30));
            return [$cmd => [
                'total'    => (clone $base)->count(),
                'sucesso'  => (clone $base)->where('status', 'sucesso')->count(),
                'falhou'   => (clone $base)->where('status', 'falhou')->count(),
                'tempo_medio' => (int) (clone $base)->where('status', 'sucesso')->avg('duracao_ms'),
            ]];
        });

        // Histórico paginado das últimas 100 execuções
        $historico = CronExecucao::latest('iniciado_em')->limit(100)->get();

        return view('super.cron.index', compact('comandos', 'ultimas', 'estatisticas', 'historico'));
    }

    public function show(CronExecucao $execucao)
    {
        return view('super.cron.show', compact('execucao'));
    }

    public function executar(Request $request, string $comando)
    {
        $dados = $request->validate(['comando' => 'sometimes|string']);

        if (!array_key_exists($comando, CronExecucao::COMANDOS_MONITORADOS)) {
            return back()->with('error', 'Comando não monitorado.');
        }

        try {
            $output = new BufferedOutput();
            Artisan::call($comando, [], $output);
            // O listener já registrou a execução. Marca como manual.
            CronExecucao::where('comando', $comando)
                ->latest('id')->limit(1)
                ->update(['origem' => 'manual']);
            return back()->with('success', "Comando {$comando} executado.");
        } catch (Throwable $e) {
            return back()->with('error', 'Erro: '.$e->getMessage());
        }
    }
}
