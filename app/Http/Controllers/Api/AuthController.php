<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Services\AutomacaoService;
use App\Services\PontuacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $dados = $request->validate([
            'telefone' => 'required|string',
            'password' => 'required|string',
            'empresa_slug' => 'nullable|string',
        ]);

        $query = Cliente::whereTelefone($dados['telefone'])->where('ativo', true);

        if (!empty($dados['empresa_slug'])) {
            $empresa = Empresa::where('slug', $dados['empresa_slug'])->first();
            if (!$empresa) throw ValidationException::withMessages(['empresa_slug' => 'Empresa não encontrada.']);
            $query->where('empresa_id', $empresa->id);
        }

        $cliente = $query->first();

        if (!$cliente || !Hash::check($dados['password'], $cliente->password)) {
            throw ValidationException::withMessages(['telefone' => 'Telefone ou senha inválidos.']);
        }

        $cliente->update(['ultimo_acesso' => now(), 'ultimo_ip' => $request->ip()]);
        $token = $cliente->createToken('pwa-cliente')->plainTextToken;

        return response()->json([
            'token' => $token,
            'cliente' => $this->serializarCliente($cliente),
            'empresa' => $this->serializarEmpresa($cliente->empresa),
        ]);
    }

    public function registrar(Request $request, PontuacaoService $pontuacaoService, AutomacaoService $automacaoService)
    {
        $dados = $request->validate([
            'empresa_slug' => 'required|string',
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'data_nascimento' => 'nullable|date',
            'password' => 'required|string|min:6',
            'codigo_indicacao' => 'nullable|string',
        ]);

        $empresa = Empresa::where('slug', $dados['empresa_slug'])->where('ativo', true)->firstOrFail();

        if (Cliente::where('empresa_id', $empresa->id)->whereTelefone($dados['telefone'])->exists()) {
            throw ValidationException::withMessages(['telefone' => 'Telefone já cadastrado.']);
        }

        $indicador = null;
        if (!empty($dados['codigo_indicacao'])) {
            $indicador = Cliente::where('empresa_id', $empresa->id)
                ->where('codigo_indicacao', $dados['codigo_indicacao'])->first();
        }

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'],
            'email' => $dados['email'] ?? null,
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'password' => Hash::make($dados['password']),
            'indicado_por_id' => $indicador?->id,
            'ativo' => true,
        ]);

        // Token primeiro — etapa crítica. Se isso falhar, devolve 500 limpo.
        $token = $cliente->createToken('pwa-cliente')->plainTextToken;

        // Etapas best-effort: bônus de cadastro/indicação e automação WhatsApp.
        // Se falharem, logamos mas NÃO queimamos o cadastro — o cliente já existe
        // e tem token. Refazer perderia tudo (telefone fica preso por unique).
        try {
            $regraCadastro = $empresa->regrasPontuacao()->where('tipo', 'cadastro')->where('ativo', true)->first();
            if ($regraCadastro && $regraCadastro->pontos_fixos > 0) {
                $pontuacaoService->creditar($cliente, $regraCadastro->pontos_fixos, 'cadastro', null, 'Bônus de cadastro');
            }
        } catch (Throwable $e) {
            Log::warning('Falha ao creditar bônus de cadastro', ['cliente_id' => $cliente->id, 'erro' => $e->getMessage()]);
        }

        try {
            if ($indicador) {
                $regraInd = $empresa->regrasPontuacao()->where('tipo', 'indicacao')->where('ativo', true)->first();
                if ($regraInd && $regraInd->pontos_fixos > 0) {
                    $pontuacaoService->creditar($indicador, $regraInd->pontos_fixos, 'indicacao', $cliente,
                        "Indicação convertida: {$cliente->nome}");
                }
            }
        } catch (Throwable $e) {
            Log::warning('Falha ao creditar bônus de indicação', ['cliente_id' => $cliente->id, 'erro' => $e->getMessage()]);
        }

        try {
            $automacaoService->disparar($empresa, 'boas_vindas', $cliente->fresh());
        } catch (Throwable $e) {
            Log::warning('Falha ao disparar automação de boas-vindas', ['cliente_id' => $cliente->id, 'erro' => $e->getMessage()]);
        }

        return response()->json([
            'token' => $token,
            'cliente' => $this->serializarCliente($cliente->fresh()),
            'empresa' => $this->serializarEmpresa($empresa),
        ], 201);
    }

    public function me(Request $request)
    {
        $cliente = $request->user();
        return response()->json([
            'cliente' => $this->serializarCliente($cliente),
            'empresa' => $this->serializarEmpresa($cliente->empresa),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sessão encerrada.']);
    }

    protected function serializarCliente(Cliente $c): array
    {
        return [
            'id' => $c->id,
            'nome' => $c->nome,
            'telefone' => $c->telefone,
            'email' => $c->email,
            'cpf' => $c->cpf,
            'data_nascimento' => $c->data_nascimento?->toDateString(),
            'pontos' => (float) $c->pontos_atual,
            'cashback' => (float) $c->cashback_atual,
            'codigo_qr' => $c->codigo_qr,
            'codigo_indicacao' => $c->codigo_indicacao,
            'total_compras' => $c->total_compras,
            'total_gasto' => (float) $c->total_gasto,
        ];
    }

    protected function serializarEmpresa(Empresa $e): array
    {
        return [
            'id' => $e->id,
            'slug' => $e->slug,
            'nome' => $e->nome,
            'logo' => $e->logo ? asset('storage/'.$e->logo) : null,
            'cor_primaria' => $e->cor_primaria,
            'cor_secundaria' => $e->cor_secundaria,
            'pontos_por_real' => (float) $e->pontos_por_real,
            'cashback_percentual' => (float) $e->cashback_percentual,
        ];
    }
}
