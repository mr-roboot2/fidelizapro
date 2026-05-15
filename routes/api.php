<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\RecompensaController;
use App\Http\Controllers\Api\ResgateController;
use App\Http\Controllers\Api\IndicacaoController;
use App\Http\Controllers\Api\PesquisaController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\PdvController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\BeneficioController;
use App\Http\Controllers\Api\LojaController;
use App\Http\Controllers\Api\RoletaController;
use App\Http\Controllers\Api\SorteioController;

// Públicas
Route::prefix('v1')->group(function () {
    Route::get('empresas', [EmpresaController::class, 'publicas']);
    Route::get('qr/{codigo}', [ClienteController::class, 'qr'])->where('codigo', '[A-Za-z0-9-]+');

    // Auth com throttle anti brute-force. Limite por empresa (campo
    // rate_limit_auth) — default 10/min/IP se a empresa não for resolvida.
    Route::middleware('empresa.throttle:auth')->group(function () {
        // NOTA: captcha removido das rotas /api/* nesta rodada — o PWA cliente
        // e o PWA loja ainda NÃO integram o widget Cloudflare Turnstile, então
        // ligar captcha global quebrava cadastro/login do cliente. Captcha
        // ativo apenas em /admin/login (Blade tem o widget). Pra ligar aqui,
        // precisa: 1) integrar `<script src="https://challenges.cloudflare.com/turnstile/v0/api.js">`
        // nos blades do PWA; 2) implementar render dinâmico via `window.turnstile.render`
        // antes de cada submit em app.js; 3) recolocar middleware('captcha') aqui.
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/registrar', [AuthController::class, 'registrar']);
        // OTP solicitar carrega throttle extra (otp-solicitar) por custar $$
        // em mensagens WhatsApp: 3/min/IP+telefone + 20/hora/IP fecha bomb.
        Route::post('auth/otp/solicitar', [OtpController::class, 'solicitar'])
            ->middleware('throttle:otp-solicitar');
        Route::post('auth/otp/validar', [OtpController::class, 'validar']);
        Route::post('auth/recuperar-senha', [OtpController::class, 'recuperarSenha']);
        Route::post('loja/login', [LojaController::class, 'login']);
    });

    // PDV externo (autenticado por X-Pdv-Secret). Limite configurável por
    // empresa (rate_limit_pdv) — default 60/min/IP.
    Route::middleware('empresa.throttle:pdv')->group(function () {
        Route::post('pdv/{slug}/compras', [PdvController::class, 'lancarCompra']);
    });
});

// Autenticadas (Sanctum) — throttle:api-cliente fecha DoS global. 120/min/user
// é folgado pro PWA legítimo mas barra farm/scraping/bomb. Definido em
// AppServiceProvider::boot.
Route::middleware(['auth:sanctum', 'throttle:api-cliente'])->prefix('v1')->group(function () {
    // Whitelist do RequirePasswordChanged — rotas que SEMPRE funcionam mesmo
    // com senha_temporaria=true, porque são necessárias pra completar a troca.
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::put('cliente/senha', [ClienteController::class, 'alterarSenha']);

    // Rotas do operador da loja (User da empresa). `sanctum.user` rejeita
    // token de Cliente — sem isso, Cliente token autenticava em /loja/* e
    // listava TODOS os clientes da empresa (Sanctum não diferencia
    // tokenable_type por padrão).
    Route::middleware('sanctum.user')->group(function () {
        Route::post('loja/logout', [LojaController::class, 'logout']);
        Route::get('loja/me', [LojaController::class, 'me']);
        Route::get('loja/clientes', [LojaController::class, 'buscarClientes']);
        Route::get('loja/clientes/qr/{codigo}', [LojaController::class, 'clientePorQr'])->where('codigo', '[A-Za-z0-9-]+');
        Route::post('loja/clientes', [LojaController::class, 'criarCliente']);
        Route::post('loja/compras', [LojaController::class, 'lancarCompra']);
    });

    // Demais rotas exigem senha definitiva. Cliente com senha_temporaria=true
    // recebe 403 password_change_required — PWA já lê a flag em /auth/me e
    // redireciona pra tela de troca, então em uso legítimo isso nunca dispara;
    // o middleware fecha o caminho pra atacante que pula o redirect.
    Route::middleware('senha.definitiva')->group(function () {
        Route::get('cliente/dashboard', [ClienteController::class, 'dashboard']);
        Route::get('cliente/compras', [ClienteController::class, 'historicoCompras']);
        Route::get('cliente/extrato', [ClienteController::class, 'extrato']);
        Route::get('cliente/empresas', [ClienteController::class, 'minhasEmpresas']);
        Route::put('cliente/perfil', [ClienteController::class, 'atualizarPerfil']);
        Route::post('cliente/perfil/foto', [ClienteController::class, 'uploadFoto']);
        Route::delete('cliente/perfil/foto', [ClienteController::class, 'removerFoto']);

        Route::get('recompensas', [RecompensaController::class, 'catalogo']);

        Route::get('resgates', [ResgateController::class, 'index']);
        Route::post('resgates', [ResgateController::class, 'solicitar']);

        Route::get('indicacoes', [IndicacaoController::class, 'index']);
        // Rate limit dedicado pra spam (10/min/usuario). Antifraude adicional
        // (dedupe, anti-self, cap diário) está no controller.
        Route::post('indicacoes', [IndicacaoController::class, 'indicar'])
            ->middleware('throttle:indicacao');

        Route::get('pesquisas/minha-geral', [PesquisaController::class, 'minhaGeral']);
        Route::post('pesquisas', [PesquisaController::class, 'responder']);
        Route::put('pesquisas/{id}', [PesquisaController::class, 'atualizar']);
        Route::delete('pesquisas/{id}', [PesquisaController::class, 'excluir']);

        Route::get('parceiros', [BeneficioController::class, 'listar']);
        Route::post('parceiros/cupons', [BeneficioController::class, 'gerarCupom']);
        Route::get('parceiros/meus-cupons', [BeneficioController::class, 'meusCupons']);

        Route::get('cliente/roleta/status', [RoletaController::class, 'status']);
        // Limita 30 giros/min por usuário — evita farm automatizado de prêmios
        Route::post('cliente/roleta/girar', [RoletaController::class, 'girar'])->middleware('throttle:30,1');

        Route::get('cliente/sorteios', [SorteioController::class, 'index']);
        Route::get('cliente/sorteios/historico', [SorteioController::class, 'historico']);
    });
});
