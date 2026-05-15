<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\RegraPontuacao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $query = Empresa::withCount(['clientes', 'compras', 'users']);

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('cnpj', 'like', "%{$busca}%")
                  ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        $empresas = $query->orderBy('nome')->paginate(20)->withQueryString();
        return view('super.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('super.empresas.form', ['empresa' => new Empresa()]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'slug' => 'nullable|string|max:80|unique:empresas,slug',
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'endereco' => 'nullable|string|max:255',
            'cor_primaria' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'cor_secundaria' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'modo_fidelidade' => 'required|in:pontos,cashback,ambos',
            'pontos_por_real' => 'required_unless:modo_fidelidade,cashback|nullable|numeric|min:0',
            'cashback_percentual' => 'required_unless:modo_fidelidade,pontos|nullable|numeric|min:0|max:100',
            'validade_pontos_dias' => 'required_unless:modo_fidelidade,cashback|nullable|integer|min:30',
            'dias_liberar_cashback' => 'nullable|integer|min:0',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|mimetypes:image/png,image/jpeg,image/webp|max:8192',
            'logo_bg_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'logo_scale' => 'nullable|integer|min:30|max:150',
            'admin_nome' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        // Zera os campos que não correspondem ao modo escolhido
        if ($dados['modo_fidelidade'] === 'cashback') {
            $dados['pontos_por_real'] = 0;
        }
        if ($dados['modo_fidelidade'] === 'pontos') {
            $dados['cashback_percentual'] = 0;
        }

        DB::transaction(function () use ($dados, $request) {
            if ($request->hasFile('logo')) {
                $dados['logo'] = $request->file('logo')->store('logos', 'public');
            }
            $dados['ativo'] = true;
            $dados['slug'] = $dados['slug'] ?: Str::slug($dados['nome']);

            $empresa = Empresa::create(collect($dados)->except(['admin_nome', 'admin_email', 'admin_password'])->toArray());

            // Cria admin inicial
            User::create([
                'empresa_id' => $empresa->id,
                'name' => $dados['admin_nome'],
                'email' => $dados['admin_email'],
                'password' => Hash::make($dados['admin_password']),
                'role' => 'admin',
                'ativo' => true,
            ]);

            // Cria regra de pontuação padrão
            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Pontuação padrão',
                'tipo' => 'compra',
                'pontos_por_real' => $empresa->pontos_por_real,
                'multiplicador' => 1,
                'ativo' => true,
            ]);
        });

        return redirect()->route('super.empresas.index')->with('success', 'Empresa criada com admin inicial!');
    }

    public function show(Empresa $empresa)
    {
        $empresa->loadCount(['clientes', 'compras', 'users', 'recompensas', 'resgates', 'campanhas']);
        $empresa->loadSum('compras as faturamento', 'valor');
        $admins = $empresa->users()->orderBy('name')->get();
        return view('super.empresas.show', compact('empresa', 'admins'));
    }

    public function edit(Empresa $empresa)
    {
        $planos = \App\Models\Plano::where('ativo', true)->orderBy('preco_mensal')->get();
        $empresa->loadMissing('assinatura.plano');
        return view('super.empresas.form', compact('empresa', 'planos'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'slug' => "nullable|string|max:80|unique:empresas,slug,{$empresa->id}",
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'endereco' => 'nullable|string|max:255',
            'cor_primaria' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'cor_secundaria' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'modo_fidelidade' => 'required|in:pontos,cashback,ambos',
            'pontos_por_real' => 'required_unless:modo_fidelidade,cashback|nullable|numeric|min:0',
            'cashback_percentual' => 'required_unless:modo_fidelidade,pontos|nullable|numeric|min:0|max:100',
            'validade_pontos_dias' => 'required_unless:modo_fidelidade,cashback|nullable|integer|min:30',
            'dias_liberar_cashback' => 'nullable|integer|min:0',
            'plano_id' => 'nullable|exists:planos,id',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|mimetypes:image/png,image/jpeg,image/webp|max:8192',
            'logo_bg_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'logo_scale' => 'nullable|integer|min:30|max:150',
            'ativo' => 'boolean',
        ]);

        // Se mudou de plano e a empresa tem assinatura ativa, atualiza também
        $planoNovo = !empty($dados['plano_id']) ? \App\Models\Plano::find($dados['plano_id']) : null;
        if ($planoNovo && (int) $empresa->plano_id !== (int) $planoNovo->id) {
            $empresa->assinatura?->update([
                'plano_id'     => $planoNovo->id,
                'valor_mensal' => $planoNovo->preco_mensal,
            ]);
        }

        if ($dados['modo_fidelidade'] === 'cashback') {
            $dados['pontos_por_real'] = 0;
        }
        if ($dados['modo_fidelidade'] === 'pontos') {
            $dados['cashback_percentual'] = 0;
        }

        if ($request->hasFile('logo')) {
            if ($empresa->logo) Storage::disk('public')->delete($empresa->logo);
            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }
        $dados['ativo'] = $request->boolean('ativo');

        $empresa->update($dados);
        return redirect()->route('super.empresas.show', $empresa)->with('success', 'Empresa atualizada!');
    }

    public function destroy(Empresa $empresa)
    {
        if ($empresa->logo) Storage::disk('public')->delete($empresa->logo);
        $empresa->delete();
        return redirect()->route('super.empresas.index')->with('success', 'Empresa removida (com todos os dados em cascata).');
    }

    public function toggle(Empresa $empresa)
    {
        $empresa->update(['ativo' => !$empresa->ativo]);
        return back()->with('success', 'Status alterado para '.($empresa->ativo ? 'ativo' : 'inativo').'.');
    }
}
