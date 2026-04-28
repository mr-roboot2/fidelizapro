<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PdvController extends Controller
{
    /**
     * POST /api/v1/pdv/{slug}/compras
     * Header: X-Pdv-Secret: sk_xxx...
     * Body: { telefone, valor, ... } OU { cpf, valor, ... } OU { codigo_qr, valor, ... }
     *
     * Se cliente não existir e enviar nome+telefone, cria automaticamente.
     */
    public function lancarCompra(Request $request, string $slug, CompraService $compraService)
    {
        $empresa = Empresa::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $secretEnviado = $request->header('X-Pdv-Secret');
        if (!$secretEnviado || !hash_equals($empresa->pdv_secret, $secretEnviado)) {
            return response()->json(['message' => 'Credencial PDV inválida.'], 401);
        }

        $dados = $request->validate([
            'telefone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14',
            'codigo_qr' => 'nullable|string|max:64',
            'nome' => 'nullable|string|max:255',
            'data_nascimento' => 'nullable|date',
            'valor' => 'required|numeric|min:0.01',
            'desconto' => 'nullable|numeric|min:0',
            'codigo' => 'nullable|string|max:50',
            'descricao' => 'nullable|string|max:255',
            'meta' => 'nullable|array',
        ]);

        if (empty($dados['telefone']) && empty($dados['cpf']) && empty($dados['codigo_qr'])) {
            return response()->json(['message' => 'Informe telefone, cpf ou codigo_qr para identificar o cliente.'], 422);
        }

        // Localiza cliente
        $cliente = Cliente::where('empresa_id', $empresa->id)
            ->when(!empty($dados['telefone']), fn($q) => $q->where('telefone', $dados['telefone']))
            ->when(!empty($dados['cpf']), fn($q) => $q->orWhere('cpf', $dados['cpf']))
            ->when(!empty($dados['codigo_qr']), fn($q) => $q->orWhere('codigo_qr', $dados['codigo_qr']))
            ->first();

        $clienteCriado = false;
        if (!$cliente) {
            if (empty($dados['telefone']) || empty($dados['nome'])) {
                return response()->json(['message' => 'Cliente não encontrado. Para auto-cadastro envie nome + telefone.'], 404);
            }
            $cliente = Cliente::create([
                'empresa_id' => $empresa->id,
                'nome' => $dados['nome'],
                'telefone' => $dados['telefone'],
                'cpf' => $dados['cpf'] ?? null,
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'password' => Hash::make(substr(preg_replace('/\D/', '', $dados['telefone']), -6)),
                'aceita_whatsapp' => true,
            ]);
            $clienteCriado = true;
        }

        $compra = $compraService->registrar($cliente, [
            'valor' => $dados['valor'],
            'desconto' => $dados['desconto'] ?? 0,
            'codigo' => $dados['codigo'] ?? null,
            'descricao' => $dados['descricao'] ?? 'Lançado via PDV',
            'origem' => 'pdv',
            'meta' => $dados['meta'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Compra registrada via PDV.',
            'cliente_criado' => $clienteCriado,
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'pontos_atual' => (float) $cliente->fresh()->pontos_atual,
                'cashback_atual' => (float) $cliente->fresh()->cashback_atual,
            ],
            'compra' => [
                'id' => $compra->id,
                'valor' => (float) $compra->valor,
                'pontos_gerados' => (float) $compra->pontos_gerados,
                'cashback_gerado' => (float) $compra->cashback_gerado,
                'created_at' => $compra->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
