<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Generator as QrGenerator;

class ClienteController extends Controller
{
    /**
     * SVG do QR de um cliente (público — codigo_qr é visualmente público).
     */
    public function qr(string $codigo)
    {
        abort_unless(Cliente::where('codigo_qr', $codigo)->exists(), 404);
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
        $transacoes = $request->user()->transacoesPontos()->latest()->take(50)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'data' => $t->created_at->format('d/m/Y H:i'),
                'tipo' => $t->tipo,
                'origem' => $t->origem,
                'pontos' => (float) $t->pontos,
                'saldo_posterior' => (float) $t->saldo_posterior,
                'descricao' => $t->descricao,
            ]);

        return response()->json(['transacoes' => $transacoes]);
    }

    public function atualizarPerfil(Request $request)
    {
        $cliente = $request->user();
        $dados = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'cpf' => 'nullable|string|max:14',
            'data_nascimento' => 'nullable|date',
            'aceita_whatsapp' => 'boolean',
        ]);

        $cliente->update($dados);
        return response()->json(['cliente' => $cliente->fresh(), 'message' => 'Perfil atualizado!']);
    }

    public function alterarSenha(Request $request)
    {
        $dados = $request->validate([
            'senha_atual' => 'required|string',
            'senha_nova'  => 'required|string|min:6|confirmed',
        ]);

        $cliente = $request->user();

        if (!Hash::check($dados['senha_atual'], $cliente->password)) {
            throw ValidationException::withMessages(['senha_atual' => 'Senha atual incorreta.']);
        }

        $cliente->update(['password' => Hash::make($dados['senha_nova'])]);

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }
}
