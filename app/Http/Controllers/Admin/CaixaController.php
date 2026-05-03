<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\CashbackService;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class CaixaController extends Controller
{
    public function index()
    {
        return view('admin.caixa.index');
    }

    /**
     * AJAX: busca cliente por telefone, CPF ou QR Code.
     */
    public function buscar(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3']);
        $q = trim($request->input('q'));
        $empresaId = Auth::user()->empresa_id;

        $cliente = Cliente::where('empresa_id', $empresaId)
            ->where(function ($w) use ($q) {
                $w->where('telefone', 'like', "%{$q}%")
                  ->orWhere('cpf', 'like', "%{$q}%")
                  ->orWhere('codigo_qr', $q)
                  ->orWhere('nome', 'like', "%{$q}%");
            })
            ->orderBy('nome')
            ->limit(8)
            ->get(['id', 'nome', 'telefone', 'cpf', 'codigo_qr', 'pontos_atual', 'cashback_atual', 'cashback_pendente']);

        return response()->json([
            'clientes' => $cliente->map(fn($c) => [
                'id' => $c->id,
                'nome' => $c->nome,
                'telefone' => $c->telefone,
                'cpf' => $c->cpf,
                'codigo_qr' => $c->codigo_qr,
                'pontos' => (float) $c->pontos_atual,
                'cashback' => (float) $c->cashback_atual,
                'cashback_pendente' => (float) $c->cashback_pendente,
            ]),
        ]);
    }

    /**
     * AJAX: lança compra rapidamente (com possível uso de cashback).
     */
    public function lancar(Request $request, CompraService $compraService, CashbackService $cashbackService)
    {
        $dados = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'valor' => 'required|numeric|min:0.01',
            'usar_cashback' => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string|max:255',
        ]);

        $empresaId = Auth::user()->empresa_id;
        $cliente = Cliente::where('id', $dados['cliente_id'])
            ->where('empresa_id', $empresaId)->firstOrFail();

        $valorBruto = round((float) $dados['valor'], 2);
        $usarCashback = round((float) ($dados['usar_cashback'] ?? 0), 2);
        $saldoDisponivel = round((float) $cliente->cashback_atual, 2);

        // Limita ao saldo disponível (compara em centavos pra evitar erro de float)
        if ($usarCashback > $saldoDisponivel) {
            return response()->json(['message' => 'Cashback solicitado maior que o disponível.'], 422);
        }
        if ($usarCashback > $valorBruto) {
            return response()->json(['message' => 'Cashback maior que o valor da compra.'], 422);
        }

        // Débito do cashback + registro da compra precisam ser atômicos —
        // se a compra falhar, o débito é revertido junto.
        try {
            $compra = DB::transaction(function () use ($cliente, $usarCashback, $valorBruto, $dados, $cashbackService, $compraService) {
                if ($usarCashback > 0) {
                    $cashbackService->debitar($cliente, $usarCashback, 'utilizacao', null,
                        "Cashback usado em compra (R$ ".number_format($valorBruto, 2, ',', '.').")");
                }

                return $compraService->registrar($cliente, [
                    'user_id' => Auth::id(),
                    'valor' => $valorBruto,
                    'desconto' => $usarCashback,
                    'descricao' => $dados['descricao'] ?? null,
                    'origem' => 'manual',
                ]);
            });
        } catch (Throwable $e) {
            Log::error('[Caixa] Falha ao lançar compra: '.$e->getMessage(), [
                'cliente_id' => $cliente->id,
                'valor' => $valorBruto,
                'usar_cashback' => $usarCashback,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Erro ao registrar compra: '.$e->getMessage(),
            ], 500);
        }

        $cliente->refresh();

        return response()->json([
            'message' => 'Compra registrada!',
            'compra' => [
                'id' => $compra->id,
                'valor' => (float) $compra->valor,
                'pontos_gerados' => (float) $compra->pontos_gerados,
                'cashback_gerado' => (float) $compra->cashback_gerado,
            ],
            'cliente' => [
                'pontos' => (float) $cliente->pontos_atual,
                'cashback' => (float) $cliente->cashback_atual,
                'cashback_pendente' => (float) $cliente->cashback_pendente,
            ],
        ]);
    }

    /**
     * AJAX: cria cliente novo direto do caixa (sem senha — gerada do telefone).
     */
    public function criar(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => "required|string|max:20|unique:clientes,telefone,NULL,id,empresa_id,{$empresaId}",
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresaId,
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'],
            'cpf' => $dados['cpf'] ?? null,
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'password' => Hash::make(substr(preg_replace('/\D/', '', $dados['telefone']), -6)),
            'aceita_whatsapp' => true,
        ]);

        return response()->json([
            'message' => 'Cliente cadastrado!',
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'telefone' => $cliente->telefone,
                'pontos' => 0,
                'cashback' => 0,
                'cashback_pendente' => 0,
            ],
        ], 201);
    }
}
