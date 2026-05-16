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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        // Rate limiters nomeados.
        // admin-login: chave por email+IP. O throttle:5,1 default chaveava
        // só por IP/rota — botnet com pool de IPs brute-forceava um único
        // email à vontade. Agora cada combinação (email, IP) tem 5/min.
        // Adicional: max 20 tentativas falhas por email/15min, independente
        // do IP — fecha o brute force distribuído.
        RateLimiter::for('admin-login', function (Request $request) {
            // Normaliza: trim + lowercase. Sem trim, atacante variava
            // 'admin@x.com', 'admin@x.com ', 'admin@x.com\t' → keys
            // distintos no throttle → multiplicador de tentativas.
            $email = strtolower(trim((string) $request->input('email', '')));
            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinutes(15, 20)->by('email:'.$email),
            ];
        });

        // api-cliente: throttle global para todas as rotas auth:sanctum.
        // 120 req/min/usuario é folgado para o PWA real, mas barra DoS /
        // farm de pesquisas/sorteios / WhatsApp bomb via OTP-after-login.
        RateLimiter::for('api-cliente', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by('user:'.$request->user()->id)
                : Limit::perMinute(30)->by('ip:'.$request->ip());
        });

        // otp-solicitar: limites dedicados ao endpoint /auth/otp/solicitar.
        // Anti-WhatsApp-bomb: cada OTP enviado custa $$ pro lojista (gateway
        // de WhatsApp paga por mensagem). Sem isso, atacante varre 10000
        // telefones num único IP e queima budget mensal.
        // - 3/min/IP+telefone: limita brute em UMA vítima.
        // - 20/hora/IP: limita varredura em massa.
        // Combina com a checagem `otp_max_por_telefone` no controller
        // (3 OTPs/15min independente do IP, na linha do telefone).
        RateLimiter::for('otp-solicitar', function (Request $request) {
            $telefone = preg_replace('/\D/', '', (string) $request->input('telefone', ''));
            return [
                Limit::perMinute(3)->by('ip:'.$request->ip().'|tel:'.$telefone),
                Limit::perHour(20)->by('ip:'.$request->ip()),
            ];
        });

        // indicacao: cliente autenticado spammando POST /indicacoes
        RateLimiter::for('indicacao', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by('user:'.$request->user()->id)
                : Limit::perMinute(3)->by('ip:'.$request->ip());
        });

        // export-relatorio: PDF e CSV carregam compras inteiras em memória.
        // 5/min/user já cobre uso legítimo e bloqueia worker malicioso
        // tentando estourar memória com paralelismo.
        RateLimiter::for('export-relatorio', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(5)->by('user:'.$request->user()->id)
                : Limit::perMinute(2)->by('ip:'.$request->ip());
        });

        // validar-cupom: tela pública /parceiro/{secret}/validar. Sem
        // throttle, atacante com `secret` conhecido (visualmente público
        // em URLs impressas pra clientes apresentarem) brute-forceava
        // códigos curtos. 20/min/IP é folgado pra parceiro real e barra
        // automação.
        RateLimiter::for('validar-cupom', function (Request $request) {
            return Limit::perMinute(20)->by('ip:'.$request->ip());
        });

        // empresas-publica: listagem GET /api/v1/empresas. Não-autenticada.
        // Concorrente automatizava scraping da base de clientes do SaaS.
        // 10/min/IP atende uso legítimo (PWA carrega 1× pra mostrar
        // catálogo) e impede scraping em larga escala.
        RateLimiter::for('empresas-publica', function (Request $request) {
            return Limit::perMinute(10)->by('ip:'.$request->ip());
        });

        // cadastro-empresa: signup público em /cadastro. Sem throttle, bot
        // cria N empresas/min poluindo a base e consumindo trials. Limites
        // bem agressivos pq cadastro real é evento raro (1 empresa/lojista):
        //   - 3/hora/IP: barra automação rápida
        //   - 10/dia/IP: dificulta atacante alugar mil IPs por dia
        // Captcha (se ligado) e CSRF reforçam.
        RateLimiter::for('cadastro-empresa', function (Request $request) {
            return [
                Limit::perHour(3)->by('ip:'.$request->ip()),
                Limit::perDay(10)->by('ip:'.$request->ip()),
            ];
        });

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
