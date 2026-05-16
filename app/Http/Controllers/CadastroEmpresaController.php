<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\User;
use App\Services\AssinaturaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cadastro público de empresa (self-service signup).
 *
 * Lojista entra em /cadastro, preenche dados da empresa + admin + escolhe
 * plano, e o sistema cria empresa + user admin + assinatura em trial
 * (`trial_dias_padrao` do super), faz auto-login e redireciona pra
 * `/admin/setup` (checklist guiado já existente).
 *
 * Proteções:
 *   - Throttle `cadastro-empresa` (3/hora/IP) — definido em AppServiceProvider
 *   - Captcha (se super admin ligou em /super/configuracoes)
 *   - CSRF (rota POST sob grupo `web`)
 *   - Validações extensivas (regex em nome/cor, unique em slug/email)
 *   - Plano revalidado contra `ativo=true` no banco (defesa contra IDOR
 *     se atacante mexer no select do form)
 *   - Tudo em DB::transaction — falha em qualquer ponto rola back
 */
class CadastroEmpresaController extends Controller
{
    public function form()
    {
        // Já logado vai direto pro painel
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $planos = Plano::where('ativo', true)
            ->orderBy('preco_mensal')
            ->get();

        // Sem planos ativos não dá pra se cadastrar — super admin precisa
        // criar pelo menos um plano público antes de divulgar /cadastro.
        if ($planos->isEmpty()) {
            return view('cadastro.indisponivel');
        }

        $config = ConfiguracaoSistema::instancia();

        return view('cadastro.empresa', [
            'planos' => $planos,
            'sistema' => $config,
            'trial_dias' => (int) ($config->trial_dias_padrao ?? 7),
        ]);
    }

    public function processar(Request $request, AssinaturaService $assinaturas)
    {
        $config = ConfiguracaoSistema::instancia();

        $dados = $request->validate([
            // Dados da empresa
            'nome'                   => ['required', 'string', 'max:120', 'regex:/^[\p{L}\p{N}\s\.\-\'&]+$/u'],
            'slug'                   => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:empresas,slug'],
            'cnpj'                   => 'nullable|string|max:18',
            'telefone'               => ['required', 'string', 'max:20', new \App\Rules\TelefoneBr()],
            'email'                  => 'required|email|max:120',
            'endereco'               => 'nullable|string|max:255',
            'cor_primaria'           => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'cor_secundaria'         => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'modo_fidelidade'        => 'required|in:pontos,cashback,ambos',
            'pontos_por_real'        => 'required_unless:modo_fidelidade,cashback|nullable|numeric|min:0|max:100',
            'cashback_percentual'    => 'required_unless:modo_fidelidade,pontos|nullable|numeric|min:0|max:100',
            'validade_pontos_dias'   => 'required_unless:modo_fidelidade,cashback|nullable|integer|min:30|max:3650',
            'dias_liberar_cashback'  => 'nullable|integer|min:0|max:90',

            // Dados do admin
            'admin_name'             => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
            'admin_email'            => 'required|email|max:255|unique:users,email',
            'admin_password'         => 'required|string|min:8|confirmed',

            // Plano
            'plano_id'               => 'required|exists:planos,id',

            // Aceite
            'aceita_termos'          => 'accepted',
        ]);

        // Re-valida plano (defesa contra IDOR — atacante podia injetar
        // plano_id de plano inativo/oculto via DevTools no select)
        $plano = Plano::where('id', $dados['plano_id'])->where('ativo', true)->first();
        if (!$plano) {
            return back()->withInput()->withErrors([
                'plano_id' => 'Plano selecionado não está mais disponível. Escolha outro.',
            ]);
        }

        // Resolve slug: se usuário deixou vazio, gera a partir do nome e
        // garante unicidade adicionando sufixo numérico. Sem isso, dois
        // cadastros com mesmo nome quebravam no UNIQUE do banco e o erro
        // genérico ("não foi possível concluir") era mostrado pro lojista.
        if (empty($dados['slug'])) {
            $base = Str::slug($dados['nome']);
            if ($base === '') {
                $base = 'empresa-'.Str::lower(Str::random(6));
            }
            $slug = $base;
            $i = 2;
            while (Empresa::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
                if ($i > 100) { // sanity: nome muito popular, vira aleatório
                    $slug = $base.'-'.Str::lower(Str::random(6));
                    break;
                }
            }
            $dados['slug'] = $slug;
        }

        try {
            $resultado = DB::transaction(function () use ($dados, $plano, $assinaturas, $config) {
                $empresa = Empresa::create([
                    'nome'                  => $dados['nome'],
                    'slug'                  => $dados['slug'] ?? null, // booted() gera se vazio
                    'cnpj'                  => $dados['cnpj'] ?? null,
                    'telefone'              => $dados['telefone'],
                    'email'                 => $dados['email'],
                    'endereco'              => $dados['endereco'] ?? null,
                    'cor_primaria'          => $dados['cor_primaria'],
                    'cor_secundaria'        => $dados['cor_secundaria'],
                    'modo_fidelidade'       => $dados['modo_fidelidade'],
                    'pontos_por_real'       => $dados['pontos_por_real'] ?? 0,
                    'cashback_percentual'   => $dados['cashback_percentual'] ?? 0,
                    'validade_pontos_dias'  => $dados['validade_pontos_dias'] ?? 365,
                    'dias_liberar_cashback' => $dados['dias_liberar_cashback'] ?? 0,
                    'ativo'                 => true,
                ]);

                $user = User::create([
                    'empresa_id' => $empresa->id,
                    'name'       => $dados['admin_name'],
                    'email'      => $dados['admin_email'],
                    'password'   => Hash::make($dados['admin_password']),
                    'role'       => 'admin',
                    'ativo'      => true,
                ]);

                // Cria assinatura com trial (trial_dias_padrao do super).
                // Gateway 'mock' pra trial — quando trial expira, super admin
                // ou cron gera primeira cobrança real (Asaas).
                $assinaturas->criar(
                    $empresa,
                    $plano,
                    'mock',
                    (int) ($config->trial_dias_padrao ?? 7)
                );

                return ['empresa' => $empresa, 'user' => $user];
            });
        } catch (Throwable $e) {
            Log::error('[CadastroEmpresa] Falha ao criar empresa: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'admin_email' => $dados['admin_email'] ?? null,
            ]);
            return back()->withInput()->withErrors([
                'nome' => 'Não foi possível concluir o cadastro. Tente novamente em alguns minutos.',
            ]);
        }

        // Auto-login (sessão web). Regenera ID pra evitar fixation.
        Auth::guard('web')->login($resultado['user']);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.setup.index')
            ->with('success', "Bem-vindo, {$resultado['user']->name}! Você tem ".($config->trial_dias_padrao ?? 7)." dias de teste grátis pra experimentar.");
    }
}
