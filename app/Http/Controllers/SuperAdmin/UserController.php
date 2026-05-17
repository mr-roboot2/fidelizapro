<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('empresa');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('name', 'like', "%{$busca}%")->orWhere('email', 'like', "%{$busca}%");
            });
        }
        if ($empresaId = $request->input('empresa_id')) $query->where('empresa_id', $empresaId);

        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        return view('super.users.index', compact('users', 'empresas'));
    }

    public function create()
    {
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        return view('super.users.form', ['user' => new User(), 'empresas' => $empresas]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'empresa_id' => 'nullable|exists:empresas,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            // min:8 alinhado com cliente/install/admin — antes era min:6,
            // permitindo super malicioso criar contas com senha trivial.
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,admin,gerente,atendente',
        ]);

        $dados['password'] = Hash::make($dados['password']);
        $dados['ativo'] = true;
        if ($dados['role'] === 'super_admin') $dados['empresa_id'] = null;

        User::create($dados);
        return redirect()->route('super.users.index')->with('success', 'Usuário criado!');
    }

    public function edit(User $user)
    {
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        return view('super.users.form', compact('user', 'empresas'));
    }

    public function update(Request $request, User $user)
    {
        $dados = $request->validate([
            'empresa_id' => 'nullable|exists:empresas,id',
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:super_admin,admin,gerente,atendente',
            'ativo' => 'boolean',
        ]);

        $senhaTrocada = !empty($dados['password']);
        $hashed = $senhaTrocada ? Hash::make($dados['password']) : null;
        unset($dados['password']);

        $dados['ativo'] = $request->boolean('ativo');
        if ($dados['role'] === 'super_admin') $dados['empresa_id'] = null;

        // Detecta o que muda ANTES de qualquer write — define se precisamos
        // revogar tokens. Email muda também invalida tokens: troca de
        // email é fluxo crítico de account recovery (admin trocou pra
        // conter conta comprometida) e tokens antigos não podem
        // sobreviver à mudança.
        $roleMudou    = isset($dados['role'])  && $dados['role']  !== $user->role;
        $emailMudou   = isset($dados['email']) && $dados['email'] !== $user->email;
        $foiInativado = $user->ativo && !$dados['ativo'];
        $precisaRevogar = $senhaTrocada || $roleMudou || $emailMudou || $foiInativado;

        // Revoga tokens PRIMEIRO (antes do update). Se algo falhar no update,
        // os tokens já foram invalidados — estado conservador. Antes a
        // revogação rodava depois → race se o update lançasse exception.
        // Além de Sanctum tokens, derruba sessões web do user: atacante
        // com cookie roubado mantinha sessão ativa mesmo após admin
        // trocar a senha. Funciona com session driver=database (default
        // após instalação); em outros drivers o middleware AdminAuth
        // ainda derruba na próxima request (ativo=false ou senha
        // diferente em re-auth periódico), mas DB é o caminho rápido.
        if ($precisaRevogar) {
            $user->tokens()->delete();
            try {
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();
            } catch (\Throwable $e) {
                // session driver pode não ser 'database' — ignorar silencioso
            }
        }

        // Aplica senha + rotaciona remember_token em UM ÚNICO save antes do
        // update do resto. Observador Auditavel grava 1 log com `password` e
        // `remember_token` ambos como '[REDACTED]' (via redactSensiveis), em
        // vez de 2 logs separados.
        if ($senhaTrocada) {
            $user->password = $hashed;
            $user->remember_token = \Illuminate\Support\Str::random(60);
            $user->save();
        }

        $user->update($dados);

        return redirect()->route('super.users.index')->with('success', 'Usuário atualizado!');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Você não pode excluir a si mesmo.');
        }
        $user->delete();
        return back()->with('success', 'Usuário excluído.');
    }
}
