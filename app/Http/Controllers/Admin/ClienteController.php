<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\PlanoLimiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = Cliente::where('empresa_id', $empresaId);

        if ($busca = $request->input('busca')) {
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
            'nome' => 'required|string|max:255',
            'telefone' => "required|string|max:20|unique:clientes,telefone,NULL,id,empresa_id,{$empresaId}",
            'email' => 'nullable|email|max:255',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
        ]);

        $dados['empresa_id'] = $empresaId;
        $dados['password'] = Hash::make(substr(preg_replace('/\D/', '', $dados['telefone']), -6));
        $dados['aceita_whatsapp'] = $request->boolean('aceita_whatsapp', true);

        Cliente::create($dados);
        return redirect()->route('admin.clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Cliente $cliente)
    {
        $this->autorizar($cliente);
        $cliente->load(['compras' => fn($q) => $q->latest()->take(20),
                       'resgates.recompensa', 'transacoesPontos' => fn($q) => $q->latest()->take(20)]);
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
            'nome' => 'required|string|max:255',
            'telefone' => "required|string|max:20|unique:clientes,telefone,{$cliente->id},id,empresa_id,{$empresaId}",
            'email' => 'nullable|email|max:255',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
            'ativo' => 'boolean',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

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

    protected function autorizar(Cliente $cliente): void
    {
        abort_if($cliente->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
