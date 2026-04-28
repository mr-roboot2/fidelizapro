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
        } catch (\Throwable $e) {
            return view('parceiro_publico.validar', [
                'parceiro' => $parceiro,
                'erro' => $e->getMessage(),
                'codigo_tentado' => $dados['codigo'],
            ]);
        }
    }
}
