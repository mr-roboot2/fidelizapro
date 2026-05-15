<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Generator as QrGenerator;

class ClienteController extends Controller
{
    /**
     * SVG do QR de um cliente.
     *
     * Endpoint público — `codigo_qr` é visualmente público (cliente mostra
     * no celular). Antes fazíamos `abort_unless($exists, 404)`, o que
     * permitia enumerar códigos válidos via tempo/resposta. Agora geramos
     * o SVG SEM checar existência: códigos inexistentes simplesmente
     * "não funcionam" quando alguém tenta lê-los no caixa (lookup é
     * server-side noutro endpoint). Sem 404 ≠ sem informação.
     */
    public function qr(string $codigo)
    {
        $svg = (new QrGenerator())->format('svg')->size(240)->margin(1)->generate($codigo);
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function dashboard(Request $request)
    {
        $cliente = $request->user();

        return response()->json([
            'pontos' => (float) $cliente->pontos_atual,
            'cashback' => (float) $cliente->cashback_atual,
            'cashback_pendente' => (float) $cliente->cashback_pendente,
            'total_gasto' => (float) $cliente->total_gasto,
            'total_compras' => $cliente->total_compras,
            'ultima_compra' => $cliente->ultima_compra?->toDateTimeString(),
        ]);
    }

    public function historicoCompras(Request $request)
    {
        $compras = $request->user()->compras()->latest()->take(50)->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'data' => $c->created_at->toDateTimeString(),
                'data_formatada' => $c->created_at->format('d/m/Y H:i'),
                'valor' => (float) $c->valor,
                'pontos_gerados' => (float) $c->pontos_gerados,
                'cashback_gerado' => (float) $c->cashback_gerado,
                'descricao' => $c->descricao,
            ]);

        return response()->json(['compras' => $compras]);
    }

    public function extrato(Request $request)
    {
        $cliente = $request->user();

        $pontos = $cliente->transacoesPontos()->latest()->take(50)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'data' => $t->created_at->format('d/m/Y H:i'),
                'tipo' => $t->tipo,
                'origem' => $t->origem,
                'pontos' => (float) $t->pontos,
                'saldo_posterior' => (float) $t->saldo_posterior,
                'descricao' => $t->descricao,
            ]);

        $cashback = $cliente->movimentosCashback()->latest()->take(50)->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'data' => $m->created_at->format('d/m/Y H:i'),
                'tipo' => $m->tipo,
                'origem' => $m->origem,
                'valor' => (float) $m->valor,
                'saldo_posterior' => (float) $m->saldo_posterior,
                'descricao' => $m->descricao,
                'processado' => (bool) $m->processado,
                'liberado_em' => $m->liberado_em?->format('d/m/Y'),
            ]);

        return response()->json([
            'pontos' => $pontos,
            'cashback' => $cashback,
        ]);
    }

    public function atualizarPerfil(Request $request)
    {
        $cliente = $request->user();
        $dados = $request->validate([
            'nome' => ['sometimes','string','max:120','regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
            'email' => 'nullable|email',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
        ]);

        // CPF é imutável após cadastro inicial — evita fraude de duplicatas
        // / contorno de bloqueios. Cliente sem CPF pode definir uma vez.
        if ($cliente->cpf && isset($dados['cpf']) && $dados['cpf'] !== $cliente->cpf) {
            unset($dados['cpf']);
        }

        // Allowlist explícita — não confia em $guarded (defesa em profundidade)
        $cliente->fill(array_intersect_key($dados, array_flip(
            ['nome','email','cpf','data_nascimento','aceita_whatsapp']
        )))->save();

        return response()->json(['cliente' => $cliente->fresh(), 'message' => 'Perfil atualizado!']);
    }

    public function uploadFoto(Request $request)
    {
        $request->validate([
            // mimetypes: alinha com os outros uploads e fecha bypass via
            // extensão duplicada (`foto.php.jpg` com magic bytes JPEG).
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:4096',
        ]);

        $cliente = $request->user();

        if ($cliente->foto) {
            Storage::disk('public')->delete($cliente->foto);
        }

        $caminho = $request->file('foto')->store('clientes_fotos', 'public');
        $cliente->update(['foto' => $caminho]);

        return response()->json([
            'foto' => asset('storage/'.$caminho),
            'message' => 'Foto atualizada!',
        ]);
    }

    public function removerFoto(Request $request)
    {
        $cliente = $request->user();

        if ($cliente->foto) {
            Storage::disk('public')->delete($cliente->foto);
            $cliente->update(['foto' => null]);
        }

        return response()->json(['message' => 'Foto removida.']);
    }

    /**
     * Retorna dados da empresa atual + outras empresas onde o mesmo telefone
     * está cadastrado (cliente pode ter contas separadas em múltiplas empresas).
     */
    public function minhasEmpresas(Request $request)
    {
        $cliente = $request->user();
        $cliente->load('empresa');

        $empresaAtual = [
            'id'                  => $cliente->empresa->id,
            'slug'                => $cliente->empresa->slug,
            'nome'                => $cliente->empresa->nome,
            'logo'                => $cliente->empresa->logo ? asset('storage/'.$cliente->empresa->logo) : null,
            'telefone'            => $cliente->empresa->telefone,
            'email'               => $cliente->empresa->email,
            'endereco'            => $cliente->empresa->endereco,
            'cor_primaria'        => $cliente->empresa->cor_primaria,
            'cor_secundaria'      => $cliente->empresa->cor_secundaria,
            'pontos_por_real'     => (float) $cliente->empresa->pontos_por_real,
            'cashback_percentual' => (float) $cliente->empresa->cashback_percentual,
            'pontos'              => (float) $cliente->pontos_atual,
            'cashback'            => (float) $cliente->cashback_atual,
            'cliente_desde'       => $cliente->created_at?->format('d/m/Y'),
        ];

        $vinculadas = Cliente::whereTelefone($cliente->telefone)
            ->where('empresa_id', '!=', $cliente->empresa_id)
            ->where('ativo', true)
            ->with('empresa:id,slug,nome,logo,cor_primaria,cor_secundaria,ativo')
            ->get()
            ->filter(fn($c) => $c->empresa && $c->empresa->ativo)
            ->map(fn($c) => [
                'empresa_id'     => $c->empresa->id,
                'slug'           => $c->empresa->slug,
                'nome'           => $c->empresa->nome,
                'logo'           => $c->empresa->logo ? asset('storage/'.$c->empresa->logo) : null,
                'cor_primaria'   => $c->empresa->cor_primaria,
                'cor_secundaria' => $c->empresa->cor_secundaria,
                'pontos'         => (float) $c->pontos_atual,
                'cashback'       => (float) $c->cashback_atual,
                'url'            => url('/app/'.$c->empresa->slug.'/'),
            ])
            ->values();

        return response()->json([
            'empresa_atual' => $empresaAtual,
            'vinculadas'    => $vinculadas,
        ]);
    }

    public function alterarSenha(Request $request)
    {
        $cliente = $request->user();

        // Em troca obrigatória (primeira entrada do cliente cadastrado pelo
        // caixa) não exige a senha atual — ela é temporária e conhecida pelo
        // operador, não pelo cliente.
        $regras = [
            'senha_nova' => 'required|string|min:8|confirmed',
        ];
        if (!$cliente->senha_temporaria) {
            $regras['senha_atual'] = 'required|string';
        }
        $dados = $request->validate($regras);

        if (!$cliente->senha_temporaria
            && !Hash::check($dados['senha_atual'], $cliente->password)) {
            throw ValidationException::withMessages(['senha_atual' => 'Senha atual incorreta.']);
        }

        $cliente->update([
            'password' => Hash::make($dados['senha_nova']),
            'senha_temporaria' => false,
        ]);

        // Revoga TODOS os tokens (inclusive o atual) — força re-login pós troca
        $cliente->tokens()->delete();

        // Rotaciona remember_token caso o Cliente use "remember me" em algum
        // futuro fluxo. Hoje a PWA não usa, mas defesa em profundidade.
        if (\Illuminate\Support\Facades\Schema::hasColumn('clientes', 'remember_token')) {
            $cliente->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->save();
        }

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }
}
