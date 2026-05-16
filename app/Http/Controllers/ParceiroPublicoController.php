<?php

namespace App\Http\Controllers;

use App\Models\Parceiro;
use App\Services\CupomService;
use Illuminate\Http\Request;

class ParceiroPublicoController extends Controller
{
    public function tela(string $secret)
    {
        $parceiro = Parceiro::where('validacao_secret', $secret)
            ->where('ativo', true)
            ->firstOrFail();

        return view('parceiro_publico.validar', compact('parceiro'));
    }

    public function validar(Request $request, string $secret, CupomService $service)
    {
        $parceiro = Parceiro::where('validacao_secret', $secret)
            ->where('ativo', true)
            ->firstOrFail();

        $dados = $request->validate([
            'codigo' => 'required|string|min:6|max:12',
            'observacao' => 'nullable|string|max:500',
        ]);

        try {
            $cupom = $service->validar($secret, $dados['codigo'], $dados['observacao'] ?? null);
            $cupom->load('beneficio', 'cliente');
            return view('parceiro_publico.validar', [
                'parceiro' => $parceiro,
                'sucesso' => true,
                'cupom' => $cupom,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Mensagem genérica — getMessage() vaza
            // "No query results for model [App\Models\Cupom]" → fingerprint
            // do framework + nome do model. Bot que conhece o `secret` do
            // parceiro brute-forçaria códigos e veria o stack.
            return view('parceiro_publico.validar', [
                'parceiro' => $parceiro,
                'erro' => 'Cupom inválido ou não encontrado.',
                'codigo_tentado' => $dados['codigo'],
            ]);
        } catch (\DomainException $e) {
            // DomainException (usado/expirado) é controlado pelo CupomService — safe.
            return view('parceiro_publico.validar', [
                'parceiro' => $parceiro,
                'erro' => $e->getMessage(),
                'codigo_tentado' => $dados['codigo'],
            ]);
        } catch (\Throwable $e) {
            // Catch-all: log do trace + mensagem genérica.
            \Log::error('[ParceiroPublico] Falha ao validar cupom: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return view('parceiro_publico.validar', [
                'parceiro' => $parceiro,
                'erro' => 'Não foi possível validar o cupom. Tente novamente.',
                'codigo_tentado' => $dados['codigo'],
            ]);
        }
    }
}
