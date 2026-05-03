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
use App\Http\Controllers\Admin\CampanhaController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\CaixaController;
use App\Http\Controllers\Admin\ImportacaoController;
use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\Admin\AutomacaoController;
use App\Http\Controllers\Admin\AtividadeSuspeitaController;
use App\Http\Controllers\Admin\MeuPlanoController;
use App\Http\Controllers\SuperAdmin\PlanoController as SuperPlanoController;
use App\Http\Controllers\Admin\ParceiroController;
use App\Http\Controllers\Admin\BeneficioController;
use App\Http\Controllers\ParceiroPublicoController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperDashboardController;
use App\Http\Controllers\SuperAdmin\EmpresaController as SuperEmpresaController;
use App\Http\Controllers\SuperAdmin\UserController as SuperUserController;
use App\Http\Controllers\SuperAdmin\ImpersonateController;
use App\Http\Controllers\SuperAdmin\AuditoriaController as SuperAuditoriaController;
use App\Http\Controllers\SuperAdmin\AssinaturaController as SuperAssinaturaController;
use App\Http\Controllers\WebhookPagamentoController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\DocumentoLegalPublicoController;
use App\Http\Controllers\SuperAdmin\DocumentoLegalController as SuperDocumentoLegalController;

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

// PWA cliente — modo genérico (servido estático em /public/app/)
Route::get('/app', fn() => redirect('/app/'));

// PWA white label (manifest + sw + view dinâmicos por empresa)
Route::get('/app/{slug}/', [PwaController::class, 'app'])->where('slug', '[a-z0-9-]+');
Route::get('/app/{slug}/manifest.json', [PwaController::class, 'manifest'])->where('slug', '[a-z0-9-]+');
Route::get('/app/{slug}/sw.js', [PwaController::class, 'serviceWorker'])->where('slug', '[a-z0-9-]+');

// Painel admin
Route::middleware(['admin.auth', 'empresa.scope'])->prefix('admin')->name('admin.')->group(function () {
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

    Route::get('resgates', [ResgateController::class, 'index'])->name('resgates.index');
    Route::get('resgates/{resgate}', [ResgateController::class, 'show'])->name('resgates.show');
    Route::post('resgates/{resgate}/aprovar', [ResgateController::class, 'aprovar'])->name('resgates.aprovar');
    Route::post('resgates/{resgate}/entregar', [ResgateController::class, 'entregar'])->name('resgates.entregar');
    Route::post('resgates/{resgate}/cancelar', [ResgateController::class, 'cancelar'])->name('resgates.cancelar');

    Route::get('transacoes', [TransacaoController::class, 'index'])->name('transacoes.index');

    Route::get('cashback', [CashbackController::class, 'index'])->name('cashback.index');
    Route::post('cashback/ajustar', [CashbackController::class, 'ajustar'])->name('cashback.ajustar');

    Route::resource('campanhas', CampanhaController::class)->except(['show']);
    Route::post('campanhas/{campanha}/disparar', [CampanhaController::class, 'disparar'])->name('campanhas.disparar');

    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');

    Route::get('configuracoes', [ConfiguracaoController::class, 'edit'])->name('configuracoes.edit');
    Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');

    Route::get('importacao', [ImportacaoController::class, 'index'])->name('importacao.index');
    Route::post('importacao', [ImportacaoController::class, 'processar'])->name('importacao.processar');

    Route::get('whatsapp', [WhatsappController::class, 'edit'])->name('whatsapp.edit');
    Route::put('whatsapp', [WhatsappController::class, 'update'])->name('whatsapp.update');
    Route::post('whatsapp/testar', [WhatsappController::class, 'testar'])->name('whatsapp.testar');

    Route::resource('automacoes', AutomacaoController::class);
    Route::post('automacoes/{automacao}/toggle', [AutomacaoController::class, 'toggle'])->name('automacoes.toggle');
    Route::post('automacoes/{automacao}/executar', [AutomacaoController::class, 'executarAgora'])->name('automacoes.executar');

    Route::get('atividade-suspeita', [AtividadeSuspeitaController::class, 'index'])->name('atividade.suspeita');
    Route::get('meu-plano', [MeuPlanoController::class, 'index'])->name('meu-plano.index');

    Route::get('parceiros/relatorio', [ParceiroController::class, 'relatorio'])->name('parceiros.relatorio');
    Route::resource('parceiros', ParceiroController::class);
    Route::get('parceiros/{parceiro}/beneficios/novo', [BeneficioController::class, 'create'])->name('beneficios.create');
    Route::post('parceiros/{parceiro}/beneficios', [BeneficioController::class, 'store'])->name('beneficios.store');
    Route::get('beneficios/{beneficio}/editar', [BeneficioController::class, 'edit'])->name('beneficios.edit');
    Route::put('beneficios/{beneficio}', [BeneficioController::class, 'update'])->name('beneficios.update');
    Route::delete('beneficios/{beneficio}', [BeneficioController::class, 'destroy'])->name('beneficios.destroy');
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
});

// Webhooks de gateway de pagamento (públicos)
Route::post('/webhook/pagamento/{gateway}', [WebhookPagamentoController::class, 'receber'])->name('webhook.pagamento');

// Mock de pagamento (dev)
Route::get('/pagamento-mock/{cobranca}', [WebhookPagamentoController::class, 'pagamentoMock'])->name('pagamento.mock');
Route::post('/pagamento-mock/{cobranca}/confirmar', [WebhookPagamentoController::class, 'confirmarPagamentoMock'])->name('pagamento.mock.confirmar');

// Páginas legais/institucionais (slug livre, editáveis pelo super admin).
// Tem que ser a ÚLTIMA rota — só captura segmentos que não bateram em nada antes.
Route::get('/{slug}', [DocumentoLegalPublicoController::class, 'show'])
    ->where('slug', '[a-z][a-z0-9-]*')
    ->name('documento.publico');
