<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\CashbackService;
use App\Services\PlanoLimiteService;
use App\Services\PontuacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = Cliente::where('empresa_id', $empresaId);

        // Busca exige min 2 chars: LIKE '%X%' (1 char) força full table scan
        // em base grande sem usar índice. Caracter único também não tem valor
        // discriminativo prático.
        $busca = trim((string) $request->input('busca', ''));
        if (mb_strlen($busca) >= 2) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('telefone', 'like', "%{$busca}%")
                  ->orWhere('email', 'like', "%{$busca}%")
                  ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        $clientes = $query->orderBy('nome')->paginate(20)->withQueryString();
        return view('admin.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('admin.clientes.form', ['cliente' => new Cliente()]);
    }

    public function store(Request $request, PlanoLimiteService $planos)
    {
        $empresaId = Auth::user()->empresa_id;
        try {
            $planos->garantirCapacidade(Auth::user()->empresa, 'clientes');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $dados = $request->validate([
            'nome' => ['required','string','max:120','regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
            'telefone' => "required|string|max:20|unique:clientes,telefone,NULL,id,empresa_id,{$empresaId}",
            'email' => 'nullable|email|max:255',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
        ]);

        $dados['empresa_id'] = $empresaId;
        // Senha inicial = últimos 6 dígitos do telefone. senha_temporaria=true
        // força troca antes do primeiro uso.
        $dados['password'] = Hash::make(substr(preg_replace('/\D/', '', $dados['telefone']), -6));
        $dados['senha_temporaria'] = true;
        $dados['aceita_whatsapp'] = $request->boolean('aceita_whatsapp', true);

        Cliente::create($dados);
        return redirect()->route('admin.clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Cliente $cliente)
    {
        $this->autorizar($cliente);
        $cliente->load([
            'compras' => fn($q) => $q->latest()->take(20),
            'resgates.recompensa',
            'transacoesPontos' => fn($q) => $q->latest()->take(50),
            'movimentosCashback' => fn($q) => $q->latest()->take(50),
        ]);
        return view('admin.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        $this->autorizar($cliente);
        return view('admin.clientes.form', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->autorizar($cliente);
        $empresaId = Auth::user()->empresa_id;

        $dados = $request->validate([
            'nome' => ['required','string','max:120','regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
            'telefone' => "required|string|max:20|unique:clientes,telefone,{$cliente->id},id,empresa_id,{$empresaId}",
            'email' => 'nullable|email|max:255',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
            'ativo' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // CPF é imutável após cadastro inicial (fraude de duplicatas)
        if ($cliente->cpf && isset($dados['cpf']) && $dados['cpf'] !== $cliente->cpf) {
            unset($dados['cpf']);
        }

        $dados['aceita_whatsapp'] = $request->boolean('aceita_whatsapp');
        $dados['ativo'] = $request->boolean('ativo');
        if (!empty($dados['password'])) {
            $dados['password'] = Hash::make($dados['password']);
        } else {
            unset($dados['password']);
        }
        $cliente->update($dados);

        return redirect()->route('admin.clientes.show', $cliente)->with('success', 'Cliente atualizado!');
    }

    public function destroy(Cliente $cliente)
    {
        $this->autorizar($cliente);
        $cliente->delete();
        return redirect()->route('admin.clientes.index')->with('success', 'Cliente excluído.');
    }

    public function ajustarPontos(Request $request, Cliente $cliente, PontuacaoService $pontos)
    {
        $this->autorizar($cliente);

        $dados = $request->validate([
            // |max: cap defensivo. Sem ele, operador com erro de digitação
            // (999999999) ou malicioso credita valor absurdo. 1 milhão de
            // pontos/cashback num único ajuste já é claramente operacional.
            'valor'  => 'required|numeric|not_in:0|between:-1000000,1000000',
            'motivo' => 'required|string|max:255',
        ]);

        $valor = (float) $dados['valor'];
        $descricao = 'Ajuste manual: '.$dados['motivo'];

        try {
            if ($valor > 0) {
                $pontos->creditar($cliente, $valor, 'manual', null, $descricao);
                $msg = "Creditados ".number_format($valor, 0, ',', '.')." pontos.";
            } else {
                $pontos->debitar($cliente, abs($valor), 'manual', null, $descricao);
                $msg = "Debitados ".number_format(abs($valor), 0, ',', '.')." pontos.";
            }
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $msg);
    }

    public function ajustarCashback(Request $request, Cliente $cliente, CashbackService $cashback)
    {
        $this->autorizar($cliente);

        $dados = $request->validate([
            // |max: cap defensivo. Sem ele, operador com erro de digitação
            // (999999999) ou malicioso credita valor absurdo. 1 milhão de
            // pontos/cashback num único ajuste já é claramente operacional.
            'valor'  => 'required|numeric|not_in:0|between:-1000000,1000000',
            'motivo' => 'required|string|max:255',
        ]);

        $valor = (float) $dados['valor'];
        $descricao = 'Ajuste manual: '.$dados['motivo'];

        try {
            if ($valor > 0) {
                $cashback->creditar($cliente, $valor, 'manual', null, $descricao);
                $msg = "Creditados R$ ".number_format($valor, 2, ',', '.')." de cashback.";
            } else {
                $cashback->debitar($cliente, abs($valor), 'manual', null, $descricao);
                $msg = "Debitados R$ ".number_format(abs($valor), 2, ',', '.')." de cashback.";
            }
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $msg);
    }

    protected function autorizar(Cliente $cliente): void
    {
        abort_if($cliente->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
