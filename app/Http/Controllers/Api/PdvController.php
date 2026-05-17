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

        // Empresa em bloqueio_total (cancelada/30+ dias atraso): PDV
        // externo NÃO deve continuar creditando pontos/cashback.
        if ($empresa->statusInadimplencia() === 'bloqueio_total') {
            return response()->json([
                'error' => 'empresa_bloqueada',
                'message' => 'Esta empresa está com a assinatura bloqueada.',
            ], 403);
        }

        $dados = $request->validate([
            'telefone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14',
            'codigo_qr' => 'nullable|string|max:64',
            'nome' => ['nullable','string','max:120','regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
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

        // Localiza cliente — IDOR fix: agrupa OR dentro de closure pra
        // garantir que `empresa_id` filtra TUDO. Sem os parênteses, o SQL
        // gerado vira `empresa_id=X AND telefone=Y OR cpf=Z OR codigo_qr=W`,
        // e por precedência `AND` liga só com `telefone`, deixando cpf/qr
        // escapar do escopo da empresa.
        $cliente = Cliente::where('empresa_id', $empresa->id)
            ->where(function ($q) use ($dados) {
                $q->when(!empty($dados['telefone']), fn($qq) => $qq->orWhere('telefone', $dados['telefone']));
                $q->when(!empty($dados['cpf']), fn($qq) => $qq->orWhere('cpf', $dados['cpf']));
                $q->when(!empty($dados['codigo_qr']), fn($qq) => $qq->orWhere('codigo_qr', $dados['codigo_qr']));
            })
            ->first();

        $clienteCriado = false;
        if (!$cliente) {
            if (empty($dados['telefone']) || empty($dados['nome'])) {
                return response()->json(['message' => 'Cliente não encontrado. Para auto-cadastro envie nome + telefone.'], 404);
            }
            // Senha inicial = últimos 6 dígitos do telefone. senha_temporaria=true
            // força troca no primeiro acesso ao PWA cliente.
            $cliente = Cliente::create([
                'empresa_id' => $empresa->id,
                'nome' => $dados['nome'],
                'telefone' => $dados['telefone'],
                'cpf' => $dados['cpf'] ?? null,
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'password' => Hash::make(substr(preg_replace('/\D/', '', $dados['telefone']), -6)),
                'senha_temporaria' => true,
                'aceita_whatsapp' => true,
            ]);
            $clienteCriado = true;
        }

        // Idempotency key: se o PDV mandar `codigo` (id da venda na ERP) e
        // já existir compra com mesmo (empresa_id, codigo), devolve a
        // existente em vez de duplicar. Sem isso, retry/timeout do PDV
        // (cliente faz retry sem saber que primeiro chegou) gerava N
        // compras pra mesma venda, creditando pontos/cashback várias
        // vezes. Defesa em profundidade: unique parcial no banco
        // (migration 2026_05_17_000002) e check aqui pra retornar 200
        // amigável ao invés de QueryException 500.
        if (!empty($dados['codigo'])) {
            $existente = \App\Models\Compra::where('empresa_id', $empresa->id)
                ->where('codigo', $dados['codigo'])
                ->first();
            if ($existente) {
                return response()->json([
                    'message' => 'Compra com este código já foi registrada anteriormente.',
                    'cliente_criado' => false,
                    'idempotent_replay' => true,
                    'compra' => [
                        'id' => $existente->id,
                        'valor' => (float) $existente->valor,
                        'pontos_gerados' => (float) $existente->pontos_gerados,
                        'cashback_gerado' => (float) $existente->cashback_gerado,
                        'created_at' => $existente->created_at->toIso8601String(),
                    ],
                ], 200);
            }
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
