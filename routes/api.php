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
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/registrar', [AuthController::class, 'registrar']);
        Route::post('auth/otp/solicitar', [OtpController::class, 'solicitar']);
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

// Autenticadas (Sanctum)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('cliente/dashboard', [ClienteController::class, 'dashboard']);
    Route::get('cliente/compras', [ClienteController::class, 'historicoCompras']);
    Route::get('cliente/extrato', [ClienteController::class, 'extrato']);
    Route::get('cliente/empresas', [ClienteController::class, 'minhasEmpresas']);
    Route::put('cliente/perfil', [ClienteController::class, 'atualizarPerfil']);
    Route::post('cliente/perfil/foto', [ClienteController::class, 'uploadFoto']);
    Route::delete('cliente/perfil/foto', [ClienteController::class, 'removerFoto']);
    Route::put('cliente/senha', [ClienteController::class, 'alterarSenha']);

    Route::get('recompensas', [RecompensaController::class, 'catalogo']);

    Route::get('resgates', [ResgateController::class, 'index']);
    Route::post('resgates', [ResgateController::class, 'solicitar']);

    Route::get('indicacoes', [IndicacaoController::class, 'index']);
    Route::post('indicacoes', [IndicacaoController::class, 'indicar']);

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

    // PWA da loja — operadores autenticados via Sanctum (User com empresa_id)
    Route::post('loja/logout', [LojaController::class, 'logout']);
    Route::get('loja/me', [LojaController::class, 'me']);
    Route::get('loja/clientes', [LojaController::class, 'buscarClientes']);
    Route::get('loja/clientes/qr/{codigo}', [LojaController::class, 'clientePorQr'])->where('codigo', '[A-Za-z0-9-]+');
    Route::post('loja/clientes', [LojaController::class, 'criarCliente']);
    Route::post('loja/compras', [LojaController::class, 'lancarCompra']);
});
