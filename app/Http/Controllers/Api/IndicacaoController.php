<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Indicacao;
use App\Rules\TelefoneBr;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IndicacaoController extends Controller
{
    /**
     * Antifraude: limite diário de indicações por cliente. Sem isso, o
     * mesmo cliente conseguia criar milhares de registros (poluindo o
     * sistema e, se a regra `indicacao` der pontos via batch externo,
     * inflando saldo). Default 10; troca via ConfiguracaoSistema se quiser.
     */
    protected const MAX_INDICACOES_DIA = 10;

    public function index(Request $request)
    {
        $cliente = $request->user();
        $indicacoes = Indicacao::where('cliente_indicador_id', $cliente->id)
            ->with('indicado:id,nome')->latest()->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'nome_indicado' => $i->indicado?->nome ?? $i->nome_indicado,
                'telefone' => $i->telefone_indicado,
                'status' => $i->status,
                'pontos_concedidos' => (float) $i->pontos_concedidos,
                'data' => $i->created_at->format('d/m/Y'),
            ]);

        return response()->json([
            'codigo_indicacao' => $cliente->codigo_indicacao,
            'link' => url('/app/?ref='.$cliente->codigo_indicacao),
            'total_indicacoes' => $indicacoes->count(),
            'total_convertidas' => $indicacoes->where('status', 'convertido')->count(),
            'total_pontos_ganhos' => $indicacoes->sum('pontos_concedidos'),
            'indicacoes' => $indicacoes,
        ]);
    }

    public function indicar(Request $request)
    {
        $dados = $request->validate([
            'nome_indicado'     => ['required','string','max:120','regex:/^[\p{L}\p{N}\s\.\-\']+$/u'],
            'telefone_indicado' => ['required','string','max:20', new TelefoneBr()],
        ]);

        $cliente = $request->user();
        $telefoneDigits = preg_replace('/\D/', '', $dados['telefone_indicado']);
        $telefoneProprio = preg_replace('/\D/', '', $cliente->telefone);

        // Antifraude #1: não indicar a si mesmo (fechava loop de cashback/pontos).
        if ($telefoneDigits === $telefoneProprio) {
            throw ValidationException::withMessages([
                'telefone_indicado' => 'Você não pode indicar seu próprio número.',
            ]);
        }

        // Antifraude #2: dedup por (indicador, telefone) — sem isso o cliente
        // criava 100x a mesma indicação pra inflar relatórios.
        $jaIndicou = Indicacao::where('cliente_indicador_id', $cliente->id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(telefone_indicado,' ',''),'(',''),')',''),'-','') = ?", [$telefoneDigits])
            ->exists();
        if ($jaIndicou) {
            throw ValidationException::withMessages([
                'telefone_indicado' => 'Você já indicou esse telefone antes.',
            ]);
        }

        // Antifraude #3: cap diário por cliente
        $indicacoesHoje = Indicacao::where('cliente_indicador_id', $cliente->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($indicacoesHoje >= self::MAX_INDICACOES_DIA) {
            throw ValidationException::withMessages([
                'telefone_indicado' => 'Limite de '.self::MAX_INDICACOES_DIA.' indicações por dia atingido. Tente amanhã.',
            ]);
        }

        $indicacao = Indicacao::create([
            'empresa_id' => $cliente->empresa_id,
            'cliente_indicador_id' => $cliente->id,
            'nome_indicado' => $dados['nome_indicado'],
            'telefone_indicado' => $dados['telefone_indicado'],
            'status' => 'pendente',
        ]);

        return response()->json([
            'message' => 'Indicação registrada! Você receberá pontos quando seu amigo realizar a primeira compra.',
            'indicacao' => $indicacao,
        ], 201);
    }
}
