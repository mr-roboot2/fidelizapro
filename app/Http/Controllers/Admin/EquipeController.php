<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlanoLimiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * CRUD de equipe (gerentes e atendentes) escopado pela empresa logada.
 *
 * Restrições:
 *  - Lista APENAS usuários com empresa_id = empresa atual (sem vazar entre tenants).
 *  - Admin/gerente da empresa podem criar/editar role 'gerente' ou 'atendente'.
 *    NÃO podem criar 'admin' (dono da empresa) nem 'super_admin' — escalada de
 *    privilégio só por super_admin no painel /super.
 *  - Não pode editar/deletar o próprio admin "dono" da empresa (role=admin).
 *  - Não pode deletar a si mesmo.
 *  - Senha trocada / role mudou / inativado → revoga tokens Sanctum + sessions.
 */
class EquipeController extends Controller
{
    private const ROLES_PERMITIDAS = ['gerente', 'atendente'];

    public function index(Request $request, PlanoLimiteService $limites)
    {
        $empresa = Auth::user()->empresa;
        $query = User::where('empresa_id', $empresa->id);

        if ($busca = trim((string) $request->input('busca'))) {
            $query->where(function ($q) use ($busca) {
                $q->where('name', 'like', "%{$busca}%")->orWhere('email', 'like', "%{$busca}%");
            });
        }

        $usuarios = $query->orderBy('role')->orderBy('name')->paginate(20)->withQueryString();
        // Consumo do limite de atendentes do plano — mostra "3/5" no topo
        // pra dono saber quando precisa fazer upgrade. consumo['users'] conta
        // todos os users da empresa (admin + gerente + atendente).
        $consumoUsers = $limites->consumo($empresa)['users'];
        return view('admin.equipe.index', compact('usuarios', 'consumoUsers'));
    }

    public function create()
    {
        return view('admin.equipe.form', [
            'usuario' => new User(),
            'rolesPermitidas' => self::ROLES_PERMITIDAS,
        ]);
    }

    public function store(Request $request, PlanoLimiteService $limites)
    {
        $dados = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:'.implode(',', self::ROLES_PERMITIDAS),
        ]);

        // Garante limite do plano ANTES de criar. Lança DomainException com
        // mensagem "Limite do plano atingido para users: X/Y" se cheio.
        try {
            $limites->garantirCapacidade(Auth::user()->empresa, 'users');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        User::create([
            'empresa_id' => Auth::user()->empresa_id,
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => Hash::make($dados['password']),
            'role' => $dados['role'],
            'ativo' => true,
        ]);

        return redirect()->route('admin.equipe.index')->with('success', 'Membro da equipe cadastrado!');
    }

    public function edit(User $usuario)
    {
        $this->autorizar($usuario);
        return view('admin.equipe.form', [
            'usuario' => $usuario,
            'rolesPermitidas' => self::ROLES_PERMITIDAS,
        ]);
    }

    public function update(Request $request, User $usuario)
    {
        $this->autorizar($usuario);

        $dados = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$usuario->id}",
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:'.implode(',', self::ROLES_PERMITIDAS),
            'ativo' => 'boolean',
        ]);

        $senhaTrocada = !empty($dados['password']);
        $hashed = $senhaTrocada ? Hash::make($dados['password']) : null;
        unset($dados['password']);
        $dados['ativo'] = $request->boolean('ativo');

        $roleMudou    = $dados['role']  !== $usuario->role;
        $emailMudou   = $dados['email'] !== $usuario->email;
        $foiInativado = $usuario->ativo && !$dados['ativo'];
        $precisaRevogar = $senhaTrocada || $roleMudou || $emailMudou || $foiInativado;

        if ($precisaRevogar) {
            $usuario->tokens()->delete();
            try {
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            } catch (\Throwable $e) {
                // session driver pode não ser 'database' — ignorar
            }
        }

        if ($senhaTrocada) {
            $usuario->password = $hashed;
            $usuario->remember_token = Str::random(60);
            $usuario->save();
        }

        $usuario->update($dados);

        return redirect()->route('admin.equipe.index')->with('success', 'Membro atualizado!');
    }

    public function destroy(User $usuario)
    {
        $this->autorizar($usuario);
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'Você não pode excluir a si mesmo.');
        }
        $usuario->delete();
        return back()->with('success', 'Membro removido da equipe.');
    }

    /**
     * Tenant guard + role guard. Bloqueia:
     *  - User de outra empresa (escopo cruzado)
     *  - User com role 'admin' ou 'super_admin' (só super pode mexer nesses)
     */
    private function autorizar(User $usuario): void
    {
        if ($usuario->empresa_id !== Auth::user()->empresa_id) {
            abort(404);
        }
        if (!in_array($usuario->role, self::ROLES_PERMITIDAS, true)) {
            abort(403, 'Apenas o super admin pode gerenciar contas de administradores.');
        }
    }
}
