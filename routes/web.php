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
use App\Http\Controllers\Admin\RelatorioController;
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
use App\Http\Controllers\ParceiroPublicoController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperDashboardController;
use App\Http\Controllers\SuperAdmin\EmpresaController as SuperEmpresaController;
use App\Http\Controllers\SuperAdmin\UserController as SuperUserController;
use App\Http\Controllers\SuperAdmin\ImpersonateController;
use App\Http\Controllers\SuperAdmin\AuditoriaController as SuperAuditoriaController;
use App\Http\Controllers\SuperAdmin\AssinaturaController as SuperAssinaturaController;
use App\Http\Controllers\WebhookPagamentoController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\DocumentoLegalPublicoController;
use App\Http\Controllers\SuperAdmin\DocumentoLegalController as SuperDocumentoLegalController;
use App\Http\Controllers\SuperAdmin\ConfiguracaoSistemaController;
use App\Http\Controllers\SuperAdmin\WhatsappController as SuperWhatsappController;
use App\Http\Controllers\SuperAdmin\WhatsappTemplateController as SuperWhatsappTemplateController;
use App\Http\Controllers\SuperAdmin\AutomacaoController as SuperAutomacaoController;
use App\Http\Controllers\SuperAdmin\CampanhaController as SuperCampanhaController;
use App\Http\Controllers\SuperAdmin\WhatsappLogController as SuperWhatsappLogController;
use App\Http\Controllers\Admin\WhatsappLogController as AdminWhatsappLogController;

// Instalador web (auto-trava após concluir via storage/installed.lock)
Route::middleware('install.gate')->prefix('install')->group(function () {
    Route::get('/',          [InstallController::class, 'welcome']);
    Route::get('/database',  [InstallController::class, 'database']);
    Route::post('/database', [InstallController::class, 'databaseStore']);
    Route::get('/app',       [InstallController::class, 'app']);
    Route::post('/app',      [InstallController::class, 'appStore']);
    Route::get('/admin',     [InstallController::class, 'admin']);
    Route::post('/admin',    [InstallController::class, 'adminStore']);
    Route::post('/admin/skip', [InstallController::class, 'adminSkip']);
});
Route::get('/install/complete', [InstallController::class, 'complete']);

Route::get('/', fn() => redirect()->route('admin.login'));

// Autenticação admin
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login']);
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

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
Route::middleware(['admin.auth', 'empresa.scope', 'verifica.pagamento'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('caixa', [CaixaController::class, 'index'])->name('caixa.index');
    Route::get('caixa/buscar', [CaixaController::class, 'buscar'])->name('caixa.buscar');
    Route::post('caixa/lancar', [CaixaController::class, 'lancar'])->name('caixa.lancar');
    Route::post('caixa/criar', [CaixaController::class, 'criar'])->name('caixa.criar');

    Route::resource('clientes', ClienteController::class);
    Route::post('clientes/{cliente}/pontos', [ClienteController::class, 'ajustarPontos'])->name('clientes.pontos');
    Route::post('clientes/{cliente}/cashback', [ClienteController::class, 'ajustarCashback'])->name('clientes.cashback');
    Route::resource('compras', CompraController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('regras', RegraPontuacaoController::class)->except(['show']);
    Route::resource('recompensas', RecompensaController::class)->except(['show']);

    Route::get('avaliacoes', [AvaliacaoController::class, 'index'])->name('avaliacoes.index');
    Route::delete('avaliacoes/{avaliacao}', [AvaliacaoController::class, 'destroy'])->name('avaliacoes.destroy');

    Route::get('resgates', [ResgateController::class, 'index'])->name('resgates.index');
    Route::get('resgates/{resgate}', [ResgateController::class, 'show'])->name('resgates.show');
    Route::post('resgates/{resgate}/aprovar', [ResgateController::class, 'aprovar'])->name('resgates.aprovar');
    Route::post('resgates/{resgate}/entregar', [ResgateController::class, 'entregar'])->name('resgates.entregar');
    Route::post('resgates/{resgate}/cancelar', [ResgateController::class, 'cancelar'])->name('resgates.cancelar');

    Route::get('transacoes', [TransacaoController::class, 'index'])->name('transacoes.index');

    Route::get('cashback', [CashbackController::class, 'index'])->name('cashback.index');
    Route::post('cashback/ajustar', [CashbackController::class, 'ajustar'])->name('cashback.ajustar');

    // Campanhas e Automações foram movidas pro super admin — config global

    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');

    Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])->name('configuracoes.edit');
    Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');

    Route::get('importacao', [ImportacaoController::class, 'index'])->name('importacao.index');
    Route::post('importacao', [ImportacaoController::class, 'processar'])->name('importacao.processar');

    Route::get('whatsapp-logs', [AdminWhatsappLogController::class, 'index'])->name('whatsapp-logs.index');

    Route::get('atividade-suspeita', [AtividadeSuspeitaController::class, 'index'])->name('atividade.suspeita');
    Route::get('meu-plano', [MeuPlanoController::class, 'index'])->name('meu-plano.index');
    Route::post('meu-plano/upgrade/{plano}', [MeuPlanoController::class, 'upgrade'])->name('meu-plano.upgrade');

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

// Tela pública de validação de cupom (parceiro acessa por URL com secret)
Route::get('/parceiro/{secret}', [ParceiroPublicoController::class, 'tela'])->name('parceiro.publico');
Route::post('/parceiro/{secret}/validar', [ParceiroPublicoController::class, 'validar'])->name('parceiro.validar');

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
});

// Webhooks de gateway de pagamento (públicos)
Route::post('/webhook/pagamento/{gateway}', [WebhookPagamentoController::class, 'receber'])->name('webhook.pagamento');
Route::post('/webhook/pix/{token}', [\App\Http\Controllers\PixWebhookController::class, 'receber'])->name('webhook.pix');

// Webhook WhatsApp Cloud API (Meta) — global (uma WABA pra todas empresas)
Route::get('/webhook/whatsapp/meta',  [WhatsappWebhookController::class, 'verificar'])->name('webhook.whatsapp.verificar');
Route::post('/webhook/whatsapp/meta', [WhatsappWebhookController::class, 'receber'])->name('webhook.whatsapp.receber');

// Mock de pagamento (dev)
Route::get('/pagamento-mock/{cobranca}', [WebhookPagamentoController::class, 'pagamentoMock'])->name('pagamento.mock');
Route::post('/pagamento-mock/{cobranca}/confirmar', [WebhookPagamentoController::class, 'confirmarPagamentoMock'])->name('pagamento.mock.confirmar');

// Páginas legais/institucionais (slug livre, editáveis pelo super admin).
// Tem que ser a ÚLTIMA rota — só captura segmentos que não bateram em nada antes.
Route::get('/{slug}', [DocumentoLegalPublicoController::class, 'show'])
    ->where('slug', '[a-z][a-z0-9-]*')
    ->name('documento.publico');
