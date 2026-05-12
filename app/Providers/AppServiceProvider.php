<?php

namespace App\Providers;

use App\Listeners\RegistrarCronExecucao;
use App\Models\Cliente;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use App\Observers\ClienteObserver;
use App\Observers\EmpresaObserver;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Cliente::observe(ClienteObserver::class);
        Empresa::observe(EmpresaObserver::class);

        // Mesma instância pra start e finish, pra preservar o array static
        // de execucoes ativas entre os dois eventos.
        $cronListener = new RegistrarCronExecucao();
        Event::listen(CommandStarting::class, fn ($e) => $cronListener->handleStart($e));
        Event::listen(CommandFinished::class, fn ($e) => $cronListener->handleFinish($e));

        Paginator::useTailwind();
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Disponibiliza $sistema (ConfiguracaoSistema) em todas as views.
        // Try/catch protege durante o instalador, quando a tabela ainda
        // não existe.
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('configuracoes_sistema')) {
                    $view->with('sistema', ConfiguracaoSistema::instancia());
                }
            } catch (Throwable $e) {
                // banco ainda não disponível — view roda sem $sistema
            }
        });
    }
}
