<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\CompraController;
use App\Http\Controllers\Admin\RegraPontuacaoController;
use App\Http\Controllers\Admin\RecompensaController;
use App\Http\Controllers\Admin\ResgateController;
use App\Http\Controllers\Admin\TransacaoController;
use App\Http\Controllers\Admin\CashbackController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\CaixaController;
use App\Http\Controllers\Admin\ImportacaoController;
use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\Admin\WhatsappTemplateController;
use App\Http\Controllers\Admin\AvaliacaoController;
use App\Http\Controllers\Admin\AtividadeSuspeitaController;
use App\Http\Controllers\Admin\MeuPlanoController;
use App\Http\Controllers\SuperAdmin\PlanoController as SuperPlanoController;
use App\Http\Controllers\Admin\ParceiroController;
use App\Http\Controllers\Admin\BeneficioController;
use App\Http\Controllers\Admin\RoletaController;
use App\Http\Controllers\Admin\SorteioController;
use App\Http\Controllers\Admin\AIGrowthController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\ParceiroPublicoController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperDashboardController;
use App\Http\Controllers\SuperAdmin\EmpresaController as SuperEmpresaController;
use App\Http\Controllers\SuperAdmin\UserController as SuperUserController;
use App\Http\Controllers\SuperAdmin\ImpersonateController;
use App\Http\Controllers\SuperAdmin\AuditoriaController as SuperAuditoriaController;
use App\Http\Controllers\SuperAdmin\AssinaturaController as SuperAssinaturaController;
// Imports de webhook (WebhookPagamentoController, WhatsappWebhookController,
// PixWebhookController) moveram pra routes/webhooks.php. WebhookPagamentoController
// continua referenciado abaixo pelo mock /pagamento-mock (só local).
use App\Http\Controllers\WebhookPagamentoController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\DocumentoLegalPublicoController;
use App\Http\Controllers\SuperAdmin\DocumentoLegalController as SuperDocumentoLegalController;
use App\Http\Controllers\SuperAdmin\ConfiguracaoSistemaController;
use App\Http\Controllers\SuperAdmin\WhatsappController as SuperWhatsappController;
use App\Http\Controllers\SuperAdmin\WhatsappTemplateController as SuperWhatsappTemplateController;
use App\Http\Controllers\SuperAdmin\AutomacaoController as SuperAutomacaoController;
use App\Http\Controllers\SuperAdmin\CampanhaController as SuperCampanhaController;
use App\Http\Controllers\SuperAdmin\WhatsappLogController as SuperWhatsappLogController;
use App\Http\Controllers\SuperAdmin\TutorialController as SuperTutorialController;
use App\Http\Controllers\Admin\AjudaController;
use App\Http\Controllers\CadastroEmpresaController;

// Instalador web (auto-trava após concluir via storage/installed.lock).
// `/install/complete` agora está DENTRO do gate — antes ficava acessível
// publicamente após instalação e o template renderizava `base_path()`,
// vazando o caminho absoluto do servidor pra ataques orientados.
Route::middleware('install.gate')->prefix('install')->group(function () {
    Route::get('/',          [InstallController::class, 'welcome']);
    Route::get('/database',  [InstallController::class, 'database']);
    Route::post('/database', [InstallController::class, 'databaseStore']);
    Route::get('/app',       [InstallController::class, 'app']);
    Route::post('/app',      [InstallController::class, 'appStore']);
    Route::get('/admin',     [InstallController::class, 'admin']);
    Route::post('/admin',    [InstallController::class, 'adminStore']);
    Route::post('/admin/skip', [InstallController::class, 'adminSkip']);
    Route::get('/complete',  [InstallController::class, 'complete'])->name('install.complete');
});

Route::get('/', fn() => redirect()->route('admin.login'));

// Autenticação admin — throttle por email+IP (5/min/par + 20/15min/email)
// + captcha (opcional via env). Definido em AppServiceProvider::boot.
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])
    ->middleware(['throttle:admin-login', 'captcha']);
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// Cadastro público de empresa (self-service signup).
// Lojista cria a conta sozinho, escolhe plano, ganha trial automático e
// cai no /admin/setup (checklist guiado já existente).
//   - throttle:cadastro-empresa (3/h + 10/dia/IP) impede flooding
//   - captcha (se super admin ligou) impede botnet
//   - CSRF embutido no grupo `web`
Route::get('/cadastro', [CadastroEmpresaController::class, 'form'])->name('cadastro.empresa.form');
Route::post('/cadastro', [CadastroEmpresaController::class, 'processar'])
    ->middleware(['throttle:cadastro-empresa', 'captcha'])
    ->name('cadastro.empresa.processar');

// PWA cliente — modo genérico (seleção de empresa, branding via super admin)
Route::get('/app', fn() => redirect('/app/'));
Route::get('/app/', [PwaController::class, 'appGenerico']);
Route::get('/app/manifest.json', [PwaController::class, 'manifestGenerico']);

// PWA white label (manifest + sw + view dinâmicos por empresa)
Route::get('/app/{slug}/', [PwaController::class, 'app'])->where('slug', '[a-z0-9-]+');
Route::get('/app/{slug}/manifest.json', [PwaController::class, 'manifest'])->where('slug', '[a-z0-9-]+');
Route::get('/app/{slug}/sw.js', [PwaController::class, 'serviceWorker'])->where('slug', '[a-z0-9-]+');

// PWA da loja (atendente/caixa) — login com User da empresa
Route::get('/loja', fn() => redirect('/loja/'));
Route::get('/loja/', [\App\Http\Controllers\LojaPwaController::class, 'app']);
Route::get('/loja/manifest.json', [\App\Http\Controllers\LojaPwaController::class, 'manifest']);

// Painel admin
// RBAC:
//   - atendente: pode entrar e operar caixa, ver clientes/compras/cashback,
//                ajustar pontos manualmente, ver/aprovar resgates, ver
//                pesquisas, dashboard e ajuda.
//   - admin/gerente: tudo acima + configurações, regras, recompensas,
//                exclusão de cliente, importação CSV, parceiros, roleta,
//                sorteio, AI Growth, setup, meu-plano, atividade suspeita.
// Rotas restritas usam `admin.role:admin,gerente`. super_admin passa em tudo.
Route::middleware(['admin.auth', 'empresa.scope', 'verifica.pagamento'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('caixa', [CaixaController::class, 'index'])->name('caixa.index');
    Route::get('caixa/buscar', [CaixaController::class, 'buscar'])->name('caixa.buscar');
    Route::post('caixa/lancar', [CaixaController::class, 'lancar'])->name('caixa.lancar');
    Route::post('caixa/criar', [CaixaController::class, 'criar'])->name('caixa.criar');
    Route::get('caixa/cupom/{compra}', [CaixaController::class, 'cupom'])->name('caixa.cupom');

    // Listagem/visualização de cliente: liberado pra atendente. Edição/exclusão
    // e ajustes financeiros administrativos: só admin/gerente. Rota `create`
    // declarada ANTES de `{cliente}` pra que `/clientes/create` não bata na
    // rota show com {cliente}=create.
    Route::middleware('admin.role:admin,gerente')->group(function () {
        Route::get('clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('clientes',       [ClienteController::class, 'store'])->name('clientes.store');
    });
    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::middleware('admin.role:admin,gerente')->group(function () {
        // Ajuste manual de pontos é VETOR de fraude end-to-end pra atendente
        // desonesto (cria pontos → resgata → entrega). Restringido a admin/
        // gerente. Caixa lança compra via `caixa.lancar` que credita pontos
        // pela regra de pontuação configurada, sem entrada arbitrária.
        Route::post('clientes/{cliente}/pontos', [ClienteController::class, 'ajustarPontos'])->name('clientes.pontos');
        Route::get('clientes/{cliente}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::match(['put','patch'], 'clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::post('clientes/{cliente}/cashback', [ClienteController::class, 'ajustarCashback'])->name('clientes.cashback');
    });

    Route::resource('compras', CompraController::class)->only(['index', 'create', 'store', 'show']);

    // Regras de pontuação e recompensas mudam a economia do programa — só admin/gerente
    Route::middleware('admin.role:admin,gerente')->group(function () {
        Route::resource('regras', RegraPontuacaoController::class)->except(['show']);
        Route::resource('recompensas', RecompensaController::class)->except(['show']);
    });

    Route::get('avaliacoes', [AvaliacaoController::class, 'index'])->name('avaliacoes.index');
    Route::middleware('admin.role:admin,gerente')->delete('avaliacoes/{avaliacao}', [AvaliacaoController::class, 'destroy'])->name('avaliacoes.destroy');

    Route::get('resgates', [ResgateController::class, 'index'])->name('resgates.index');
    Route::get('resgates/relatorio', [ResgateController::class, 'relatorio'])->name('resgates.relatorio');
    Route::get('resgates/{resgate}', [ResgateController::class, 'show'])->name('resgates.show');
    Route::post('resgates/{resgate}/aprovar', [ResgateController::class, 'aprovar'])->name('resgates.aprovar');
    Route::post('resgates/{resgate}/entregar', [ResgateController::class, 'entregar'])->name('resgates.entregar');
    Route::middleware('admin.role:admin,gerente')->post('resgates/{resgate}/cancelar', [ResgateController::class, 'cancelar'])->name('resgates.cancelar');

    Route::get('transacoes', [TransacaoController::class, 'index'])->name('transacoes.index');

    Route::get('cashback', [CashbackController::class, 'index'])->name('cashback.index');
    Route::middleware('admin.role:admin,gerente')->post('cashback/ajustar', [CashbackController::class, 'ajustar'])->name('cashback.ajustar');

    // Campanhas e Automações foram movidas pro super admin — config global

    // Relatórios foi unificado com AI Growth — mantém a rota antiga só pra redirect
    Route::get('relatorios', fn () => redirect()->route('admin.ai-growth.index'))->name('relatorios.index');

    // Tudo aqui mexe em config / economia / dados sensíveis — só admin/gerente
    Route::middleware('admin.role:admin,gerente')->group(function () {
        Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])->name('configuracoes.edit');
        Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');

        Route::get('importacao', [ImportacaoController::class, 'index'])->name('importacao.index');
        Route::post('importacao', [ImportacaoController::class, 'processar'])->name('importacao.processar');
    });

    // AI Growth: relatórios/insights (config + visualização sensível) — só admin/gerente.
    // Exports carregam compras em memória → throttle dedicado pra impedir DoS.
    Route::middleware(['modulo:ai_growth', 'admin.role:admin,gerente'])->prefix('ai-growth')->name('ai-growth.')->group(function () {
        Route::get('/', [AIGrowthController::class, 'index'])->name('index');
        Route::middleware('throttle:export-relatorio')->group(function () {
            Route::get('/exportar-pdf', [AIGrowthController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/exportar-csv', [AIGrowthController::class, 'exportCsv'])->name('export.csv');
        });
    });

    Route::get('ajuda', [AjudaController::class, 'index'])->name('ajuda.index');

    Route::middleware('admin.role:admin,gerente')->group(function () {
        Route::get('atividade-suspeita', [AtividadeSuspeitaController::class, 'index'])->name('atividade.suspeita');
        Route::get('meu-plano', [MeuPlanoController::class, 'index'])->name('meu-plano.index');
        Route::post('meu-plano/upgrade/{plano}', [MeuPlanoController::class, 'upgrade'])->name('meu-plano.upgrade');
        Route::post('meu-plano/downgrade/{plano}', [MeuPlanoController::class, 'downgrade'])->name('meu-plano.downgrade');
        Route::post('meu-plano/cobrancas/{cobranca}/cancelar', [MeuPlanoController::class, 'cancelarCobranca'])->name('meu-plano.cobrancas.cancelar');

        Route::prefix('setup')->name('setup.')->group(function () {
            Route::get('/', [SetupController::class, 'index'])->name('index');
            Route::post('/pular', [SetupController::class, 'pular'])->name('pular');
            Route::post('/reabrir', [SetupController::class, 'reabrir'])->name('reabrir');
        });

        Route::get('pwa/compartilhar', [\App\Http\Controllers\Admin\PwaShareController::class, 'index'])->name('pwa.share');
        Route::get('pwa/cartaz', [\App\Http\Controllers\Admin\PwaShareController::class, 'cartaz'])->name('pwa.cartaz');

        Route::get('parceiros/relatorio', [ParceiroController::class, 'relatorio'])->name('parceiros.relatorio');
        Route::resource('parceiros', ParceiroController::class);
        Route::get('parceiros/{parceiro}/beneficios/novo', [BeneficioController::class, 'create'])->name('beneficios.create');
        Route::post('parceiros/{parceiro}/beneficios', [BeneficioController::class, 'store'])->name('beneficios.store');
        Route::get('beneficios/{beneficio}/editar', [BeneficioController::class, 'edit'])->name('beneficios.edit');
        Route::put('beneficios/{beneficio}', [BeneficioController::class, 'update'])->name('beneficios.update');
        Route::delete('beneficios/{beneficio}', [BeneficioController::class, 'destroy'])->name('beneficios.destroy');

        Route::middleware('modulo:roleta')->group(function () {
            Route::get('roleta', [RoletaController::class, 'index'])->name('roleta.index');
            Route::get('roleta/metricas', [RoletaController::class, 'metricas'])->name('roleta.metricas');
            Route::put('roleta/{roleta}', [RoletaController::class, 'update'])->name('roleta.update');
            Route::post('roleta/{roleta}/premios', [RoletaController::class, 'premioStore'])->name('roleta.premios.store');
            Route::put('roleta/{roleta}/premios/{premio}', [RoletaController::class, 'premioUpdate'])->name('roleta.premios.update');
            Route::delete('roleta/{roleta}/premios/{premio}', [RoletaController::class, 'premioDestroy'])->name('roleta.premios.destroy');
            Route::post('roleta/{roleta}/creditar', [RoletaController::class, 'creditar'])->name('roleta.creditar');
            Route::post('roleta/{roleta}/gatilhos', [RoletaController::class, 'gatilhoSalvar'])->name('roleta.gatilhos.salvar');
        });

        Route::middleware('modulo:sorteio')->group(function () {
            Route::get('sorteios/metricas', [SorteioController::class, 'metricas'])->name('sorteios.metricas');
            Route::resource('sorteios', SorteioController::class);
            Route::post('sorteios/{sorteio}/ativar', [SorteioController::class, 'ativar'])->name('sorteios.ativar');
            Route::post('sorteios/{sorteio}/cancelar', [SorteioController::class, 'cancelar'])->name('sorteios.cancelar');
            Route::post('sorteios/{sorteio}/finalizar', [SorteioController::class, 'finalizar'])->name('sorteios.finalizar');
            Route::post('sorteios/{sorteio}/sortear', [SorteioController::class, 'sortear'])->name('sorteios.sortear');
            Route::post('sorteios/{sorteio}/bilhetes', [SorteioController::class, 'creditarBilhete'])->name('sorteios.bilhetes');
        });
    });
});

// Tela pública de validação de cupom (parceiro acessa por URL com secret).
// Throttle dedicado: sem ele, atacante que conhece o `secret` (visualmente
// público em URLs impressas) brute-forceava códigos curtos.
Route::get('/parceiro/{secret}', [ParceiroPublicoController::class, 'tela'])->name('parceiro.publico');
Route::post('/parceiro/{secret}/validar', [ParceiroPublicoController::class, 'validar'])
    ->middleware('throttle:validar-cupom')
    ->name('parceiro.validar');

// Sair de impersonação: precisa rodar com o usuário admin impersonado
// (nesse contexto Auth::user() NÃO é super, então não pode estar no grupo super.auth).
// O controller valida a presença de impersonate_origem_id na sessão.
Route::post('super/impersonate/sair', [ImpersonateController::class, 'sair'])
    ->name('super.impersonate.sair');

// Super admin
Route::middleware(['super.auth'])->prefix('super')->name('super.')->group(function () {
    Route::get('/', [SuperDashboardController::class, 'index'])->name('dashboard');

    Route::resource('empresas', SuperEmpresaController::class);
    Route::post('empresas/{empresa}/toggle', [SuperEmpresaController::class, 'toggle'])->name('empresas.toggle');

    Route::resource('users', SuperUserController::class)->except(['show']);

    Route::post('impersonate/{empresa}', [ImpersonateController::class, 'entrar'])->name('impersonate.entrar');

    Route::get('auditoria', [SuperAuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('auditoria/{log}', [SuperAuditoriaController::class, 'show'])->name('auditoria.show');

    Route::resource('planos', SuperPlanoController::class)->except(['show']);

    Route::get('cron', [\App\Http\Controllers\SuperAdmin\CronController::class, 'index'])->name('cron.index');
    Route::get('cron/{execucao}', [\App\Http\Controllers\SuperAdmin\CronController::class, 'show'])->name('cron.show');
    Route::post('cron/executar/{comando}', [\App\Http\Controllers\SuperAdmin\CronController::class, 'executar'])
        ->where('comando', '[a-z0-9:_-]+')
        ->name('cron.executar');

    Route::get('configuracoes', [ConfiguracaoSistemaController::class, 'edit'])->name('configuracoes.edit');
    Route::put('configuracoes', [ConfiguracaoSistemaController::class, 'update'])->name('configuracoes.update');

    Route::get('whatsapp', [SuperWhatsappController::class, 'edit'])->name('whatsapp.edit');
    Route::put('whatsapp', [SuperWhatsappController::class, 'update'])->name('whatsapp.update');
    Route::post('whatsapp/testar', [SuperWhatsappController::class, 'testar'])->name('whatsapp.testar');
    Route::post('whatsapp/regenerar-webhook-token', [SuperWhatsappController::class, 'regenerarWebhookToken'])->name('whatsapp.regenerar-webhook-token');

    Route::get('whatsapp-templates', [SuperWhatsappTemplateController::class, 'index'])->name('whatsapp-templates.index');
    Route::get('whatsapp-templates/meta', [SuperWhatsappTemplateController::class, 'listarMeta'])->name('whatsapp-templates.meta');
    Route::put('whatsapp-templates/{evento}', [SuperWhatsappTemplateController::class, 'update'])->name('whatsapp-templates.update');

    // parameters() forçado pq o pluralizador do Laravel transforma "automacoes"
    // em "{automaco}" (singular tosco), e aí o nome não bate com $automacao do
    // controller — o route binding implícito falha silencioso e instancia uma
    // model vazia, fazendo o edit aparecer como create.
    Route::resource('automacoes', SuperAutomacaoController::class)->parameters(['automacoes' => 'automacao']);
    Route::post('automacoes/{automacao}/toggle', [SuperAutomacaoController::class, 'toggle'])->name('automacoes.toggle');
    Route::post('automacoes/{automacao}/executar', [SuperAutomacaoController::class, 'executarAgora'])->name('automacoes.executar');

    Route::resource('campanhas', SuperCampanhaController::class)->except(['show'])->parameters(['campanhas' => 'campanha']);
    Route::post('campanhas/{campanha}/disparar', [SuperCampanhaController::class, 'disparar'])->name('campanhas.disparar');

    Route::get('whatsapp-logs', [SuperWhatsappLogController::class, 'index'])->name('whatsapp-logs.index');

    Route::post('tutoriais/reordenar', [SuperTutorialController::class, 'reordenar'])->name('tutoriais.reordenar');
    Route::post('tutoriais/{tutorial}/toggle', [SuperTutorialController::class, 'toggle'])->name('tutoriais.toggle');
    // parameters() forçado pq o pluralizador do Laravel transforma "tutoriais"
    // em "{tutoriai}" e o route binding implícito falha com Tutorial $tutorial
    // no controller. Mesmo problema das "automacoes".
    Route::resource('tutoriais', SuperTutorialController::class)
        ->except(['show'])
        ->parameters(['tutoriais' => 'tutorial']);

    Route::get('documentos', [SuperDocumentoLegalController::class, 'index'])->name('documentos.index');
    Route::get('documentos/criar', [SuperDocumentoLegalController::class, 'create'])->name('documentos.create');
    Route::post('documentos', [SuperDocumentoLegalController::class, 'store'])->name('documentos.store');
    Route::get('documentos/{slug}/editar', [SuperDocumentoLegalController::class, 'edit'])->name('documentos.edit');
    Route::put('documentos/{slug}', [SuperDocumentoLegalController::class, 'update'])->name('documentos.update');
    Route::delete('documentos/{slug}', [SuperDocumentoLegalController::class, 'destroy'])->name('documentos.destroy');

    Route::get('assinaturas', [SuperAssinaturaController::class, 'index'])->name('assinaturas.index');
    Route::get('assinaturas/criar', [SuperAssinaturaController::class, 'create'])->name('assinaturas.create');
    Route::post('assinaturas', [SuperAssinaturaController::class, 'store'])->name('assinaturas.store');
    Route::get('assinaturas/{assinatura}', [SuperAssinaturaController::class, 'show'])->name('assinaturas.show');
    Route::post('assinaturas/{assinatura}/gerar-cobranca', [SuperAssinaturaController::class, 'gerarCobranca'])->name('assinaturas.gerar-cobranca');
    Route::post('assinaturas/{assinatura}/cancelar', [SuperAssinaturaController::class, 'cancelar'])->name('assinaturas.cancelar');
    Route::post('cobrancas/{cobranca}/marcar-paga', [SuperAssinaturaController::class, 'marcarPaga'])->name('cobrancas.marcar-paga');
    Route::get('cobrancas/{cobranca}', [SuperAssinaturaController::class, 'cobrancaShow'])->name('cobrancas.show');
    Route::post('cobrancas/{cobranca}/regerar-pix', [SuperAssinaturaController::class, 'regerarPix'])->name('cobrancas.regerar-pix');
    Route::post('cobrancas/{cobranca}/cancelar', [SuperAssinaturaController::class, 'cancelarCobranca'])->name('cobrancas.cancelar');
    Route::delete('cobrancas/{cobranca}', [SuperAssinaturaController::class, 'excluirCobranca'])->name('cobrancas.excluir');
});

// Webhooks foram movidos para routes/webhooks.php pra rodarem FORA do
// middleware group `web` (sem StartSession/EncryptCookies/etc) — antes,
// cada POST forjado criava session file em storage/framework/sessions
// antes do controller responder 401, virando vetor de DoS por inode.

// Mock de pagamento (apenas ambiente local — bloqueado em produção via
// abort_unless dentro do controller também).
if (app()->environment('local')) {
    Route::get('/pagamento-mock/{cobranca}', [WebhookPagamentoController::class, 'pagamentoMock'])->name('pagamento.mock');
    Route::post('/pagamento-mock/{cobranca}/confirmar', [WebhookPagamentoController::class, 'confirmarPagamentoMock'])->name('pagamento.mock.confirmar');
}

// Páginas legais/institucionais (slug livre, editáveis pelo super admin).
// Tem que ser a ÚLTIMA rota — só captura segmentos que não bateram em nada antes.
Route::get('/{slug}', [DocumentoLegalPublicoController::class, 'show'])
    ->where('slug', '[a-z][a-z0-9-]*')
    ->name('documento.publico');
