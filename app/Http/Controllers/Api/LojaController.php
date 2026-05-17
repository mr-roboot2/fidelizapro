<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use App\Services\CashbackService;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LojaController extends Controller
{
    public function login(Request $request)
    {
        $dados = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $dados['email'])->where('ativo', true)->first();

        // Anti-timing: roda Hash::check sempre, mesmo sem user, contra hash
        // dummy. Antes, email inexistente respondia ~5ms vs ~200ms quando
        // existia — atacante enumerava operadores via timing.
        //
        // Hash dummy precisa ser BCRYPT VÁLIDO — antes era string "invalid..."
        // que disparava RuntimeException virando HTTP 500. Este é um hash
        // bcrypt real pré-computado (cost=12), nunca bate com senha real.
        $hashAlvo = $user?->password
            ?? '$2y$12$/17LnvEzN5f9qw/Ke//0lepSYqGDnPmdsMsDDT4pXhKxBvrVAaW.u';
        $senhaConfere = Hash::check($dados['password'], $hashAlvo);

        if (!$user || !$senhaConfere) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        // Super admin não tem empresa — não pode usar a PWA da loja
        if (!$user->empresa_id) {
            throw ValidationException::withMessages(['email' => 'Esta conta não tem loja associada.']);
        }

        // Revoga tokens antigos do mesmo dispositivo (mesmo name).
        $user->tokens()->where('name', 'pwa-loja')->delete();
        $token = $user->createToken('pwa-loja')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->serializarUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->serializarUser($request->user())]);
    }

    /**
     * Busca cliente da empresa por telefone, CPF, código QR ou nome.
     */
    public function buscarClientes(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);
        $q = trim($request->input('q'));
        $empresaId = $request->user()->empresa_id;

        $clientes = Cliente::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where(function ($w) use ($q) {
                $w->where('telefone', 'like', "%{$q}%")
                  ->orWhere('cpf', 'like', "%{$q}%")
                  ->orWhere('codigo_qr', $q)
                  ->orWhere('nome', 'like', "%{$q}%");
            })
            ->orderBy('nome')
            ->limit(8)
            ->get(['id', 'nome', 'telefone', 'cpf', 'codigo_qr', 'foto', 'pontos_atual', 'cashback_atual', 'cashback_pendente']);

        return response()->json([
            'clientes' => $clientes->map(fn($c) => $this->serializarCliente($c)),
        ]);
    }

    /**
     * Carrega um cliente específico pelo código QR escaneado.
     */
    public function clientePorQr(Request $request, string $codigo)
    {
        $cliente = Cliente::where('empresa_id', $request->user()->empresa_id)
            ->where('codigo_qr', $codigo)
            ->where('ativo', true)
            ->firstOrFail(['id', 'nome', 'telefone', 'cpf', 'codigo_qr', 'foto', 'pontos_atual', 'cashback_atual', 'cashback_pendente']);

        return response()->json(['cliente' => $this->serializarCliente($cliente)]);
    }

    public function criarCliente(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $dados = $request->validate([
            'nome'            => ['required','string','max:120','regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
            'telefone'        => ['required','string','max:20', new \App\Rules\TelefoneBr(),
                "unique:clientes,telefone,NULL,id,empresa_id,{$empresaId}"],
            // CPF agora valida formato + unicidade por empresa (consistente com
            // Admin/CaixaController::criar). Antes aceitava CPF lixo e permitia
            // duplicação na mesma empresa — operador podia criar 2 clientes com
            // mesmo CPF e burlar AtividadeSuspeita.
            'cpf'             => ['nullable','string', new \App\Rules\CpfValido()],
            'data_nascimento' => 'nullable|date',
        ]);

        // Normaliza CPF e re-checa duplicata
        if (!empty($dados['cpf'])) {
            $cpfNorm = preg_replace('/\D/', '', $dados['cpf']);
            if (Cliente::where('empresa_id', $empresaId)->where('cpf', $cpfNorm)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cpf' => 'Já existe cliente com este CPF nesta empresa.',
                ]);
            }
            $dados['cpf'] = $cpfNorm;
        }

        // Senha inicial = últimos 6 dígitos do telefone (fácil de explicar para
        // o cliente). Risco mitigado por senha_temporaria=true: o PWA força
        // troca de senha antes de qualquer operação que precise de token válido.
        $cliente = Cliente::create([
            'empresa_id'       => $empresaId,
            'nome'             => $dados['nome'],
            'telefone'         => $dados['telefone'],
            'cpf'              => $dados['cpf'] ?? null,
            'data_nascimento'  => $dados['data_nascimento'] ?? null,
            'password'         => Hash::make(substr(preg_replace('/\D/', '', $dados['telefone']), -6)),
            'senha_temporaria' => true,
            'aceita_whatsapp'  => true,
        ]);

        return response()->json(['cliente' => $this->serializarCliente($cliente)], 201);
    }

    public function lancarCompra(Request $request, CompraService $compraService, CashbackService $cashbackService)
    {
        $dados = $request->validate([
            'cliente_id'    => 'required|exists:clientes,id',
            // max: 99999999.99 = cap da coluna compras.valor DECIMAL(10,2).
            // Sem max, valor além disso explodia em PDOException SQLSTATE
            // 22003 → 500 pra operador sem mensagem clara.
            'valor'         => 'required|numeric|min:0.01|max:99999999.99',
            'usar_cashback' => 'nullable|numeric|min:0',
            'descricao'     => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        // Cliente inativo NÃO recebe compra. Antes o filtro ativo só estava
        // em buscar/QR; lancarCompra aceitava se operador soubesse o id —
        // permitia reativação "fantasma" de cliente que foi inativado.
        $cliente = Cliente::where('id', $dados['cliente_id'])
            ->where('empresa_id', $user->empresa_id)
            ->where('ativo', true)
            ->firstOrFail();

        $valorBruto    = round((float) $dados['valor'], 2);
        $usarCashback  = round((float) ($dados['usar_cashback'] ?? 0), 2);
        $saldoCashback = round((float) $cliente->cashback_atual, 2);

        if ($usarCashback > $saldoCashback) {
            return response()->json(['message' => 'Cashback solicitado maior que o disponível.'], 422);
        }
        if ($usarCashback > $valorBruto) {
            return response()->json(['message' => 'Cashback maior que o valor da compra.'], 422);
        }

        try {
            $compra = DB::transaction(function () use ($cliente, $usarCashback, $valorBruto, $dados, $user, $cashbackService, $compraService) {
                if ($usarCashback > 0) {
                    $cashbackService->debitar($cliente, $usarCashback, 'utilizacao', null,
                        'Cashback usado em compra (R$ '.number_format($valorBruto, 2, ',', '.').')');
                }

                return $compraService->registrar($cliente, [
                    'user_id'   => $user->id,
                    'valor'     => $valorBruto,
                    'desconto'  => $usarCashback,
                    'descricao' => $dados['descricao'] ?? null,
                    'origem'    => 'manual',
                ]);
            });
        } catch (\DomainException $e) {
            // Erros de domínio (saldo insuficiente, regra de negócio) são
            // seguros pra exibir — mensagem já é genérica.
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[Loja PWA] Falha ao lançar compra: '.$e->getMessage(), [
                'cliente_id' => $cliente->id,
                'valor'      => $valorBruto,
                'trace'      => $e->getTraceAsString(),
            ]);
            // Mensagem genérica pro cliente — não vaza nome de coluna,
            // SQL state, etc. Detalhes ficam no log.
            return response()->json(['message' => 'Não foi possível registrar a compra. Tente novamente.'], 500);
        }

        $cliente->refresh();

        return response()->json([
            'message' => 'Compra registrada!',
            'compra'  => [
                'id'              => $compra->id,
                'valor'           => (float) $compra->valor,
                'pontos_gerados'  => (float) $compra->pontos_gerados,
                'cashback_gerado' => (float) $compra->cashback_gerado,
            ],
            'cliente' => $this->serializarCliente($cliente),
        ]);
    }

    protected function serializarUser(User $u): array
    {
        $u->loadMissing('empresa');
        return [
            'id'      => $u->id,
            'nome'    => $u->name,
            'email'   => $u->email,
            'role'    => $u->role,
            'empresa' => $u->empresa ? [
                'id'             => $u->empresa->id,
                'slug'           => $u->empresa->slug,
                'nome'           => $u->empresa->nome,
                'logo'           => $u->empresa->logo ? asset('storage/'.$u->empresa->logo) : null,
                'cor_primaria'   => $u->empresa->cor_primaria,
                'cor_secundaria' => $u->empresa->cor_secundaria,
            ] : null,
        ];
    }

    protected function serializarCliente(Cliente $c): array
    {
        return [
            'id'                => $c->id,
            'nome'              => $c->nome,
            'telefone'          => $c->telefone,
            'cpf'               => $c->cpf,
            'codigo_qr'         => $c->codigo_qr,
            'foto'              => $c->foto ? asset('storage/'.$c->foto) : null,
            'pontos'            => (float) $c->pontos_atual,
            'cashback'          => (float) $c->cashback_atual,
            'cashback_pendente' => (float) $c->cashback_pendente,
        ];
    }
}
