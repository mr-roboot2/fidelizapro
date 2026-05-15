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
            'password' => 'required|string|min:6',
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
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:super_admin,admin,gerente,atendente',
            'ativo' => 'boolean',
        ]);

        $senhaTrocada = false;
        if (!empty($dados['password'])) {
            $dados['password'] = Hash::make($dados['password']);
            $senhaTrocada = true;
        } else {
            unset($dados['password']);
        }
        $dados['ativo'] = $request->boolean('ativo');
        if ($dados['role'] === 'super_admin') $dados['empresa_id'] = null;

        $user->update($dados);

        // Quando senha é trocada via painel super, normalmente é porque o
        // operador perdeu acesso OU foi comprometido. Em ambos os casos,
        // tokens Sanctum dele continuariam válidos até 30d se não revogados.
        // Mata todos os tokens do operador pra forçar re-login.
        if ($senhaTrocada) {
            $user->tokens()->delete();
        }

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
