<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sorteio;
use App\Models\SorteioBilhete;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SorteioController extends Controller
{
    /**
     * Sorteios passados (finalizado ou cancelado) onde o cliente teve bilhete
     * ou venceu. Diferente do index, que mostra só o que tá vivo.
     */
    public function historico(Request $request)
    {
        $cliente = $request->user();

        $sorteios = Sorteio::where('empresa_id', $cliente->empresa_id)
            ->whereIn('status', ['finalizado', 'cancelado'])
            ->whereExists(function ($q) use ($cliente) {
                $q->select(DB::raw(1))
                  ->from('sorteio_bilhetes')
                  ->whereColumn('sorteio_bilhetes.sorteio_id', 'sorteios.id')
                  ->where('sorteio_bilhetes.cliente_id', $cliente->id);
            })
            ->orderByDesc('data_sorteio')
            ->with(['recompensa', 'vencedor:id,nome', 'vencedorBilhete:id,sorteio_id,numero'])
            ->limit(50)
            ->get();

        $bilhetesDoCliente = SorteioBilhete::where('cliente_id', $cliente->id)
            ->whereIn('sorteio_id', $sorteios->pluck('id'))
            ->orderBy('numero')
            ->get(['id', 'sorteio_id', 'numero'])
            ->groupBy('sorteio_id');

        return response()->json([
            'sorteios' => $sorteios->map(fn (Sorteio $s) => [
                'id'             => $s->id,
                'nome'           => $s->nome,
                'descricao'      => $s->descricao,
                'imagem'         => $s->imagem ? asset('storage/'.$s->imagem) : null,
                'data_sorteio'   => $s->data_sorteio->format('d/m/Y'),
                'status'         => $s->status,
                'recompensa'     => $s->recompensa?->nome,
                'valor_estimado' => $s->valor_estimado ? (float) $s->valor_estimado : null,
                'meus_bilhetes'  => $bilhetesDoCliente->has($s->id) ? $bilhetesDoCliente[$s->id]->count() : 0,
                'meus_numeros'   => $bilhetesDoCliente->has($s->id)
                    ? $bilhetesDoCliente[$s->id]->map(fn ($b) => '#'.str_pad((string) $b->numero, 4, '0', STR_PAD_LEFT))->all()
                    : [],
                'vencedor'         => $s->vencedor?->nome,
                'vencedor_bilhete' => $s->vencedorBilhete?->numeroFormatado(),
                'eu_venci'         => $s->vencedor_cliente_id === $cliente->id,
            ])->values(),
        ]);
    }

    /**
     * Lista sorteios da empresa do cliente:
     *  - ativos (sempre)
     *  - planejados (sempre)
     *  - sorteados nos últimos 30 dias OU em que o cliente ganhou
     *  - inclui qtd de bilhetes do cliente em cada
     */
    public function index(Request $request)
    {
        $cliente = $request->user();

        $sorteios = Sorteio::where('empresa_id', $cliente->empresa_id)
            ->whereNotIn('status', ['finalizado', 'cancelado'])
            ->where(function ($q) use ($cliente) {
                $q->whereIn('status', ['ativo', 'planejado'])
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'sorteado')->where('sorteado_em', '>=', now()->subDays(30));
                  })
                  ->orWhere(function ($q2) use ($cliente) {
                      $q2->where('status', 'sorteado')->where('vencedor_cliente_id', $cliente->id);
                  });
            })
            ->orderByRaw("FIELD(status, 'ativo', 'planejado', 'sorteado')")
            ->orderBy('data_sorteio')
            ->with(['recompensa', 'vencedor:id,nome', 'vencedorBilhete:id,sorteio_id,numero'])
            ->limit(20)
            ->get();

        // Bilhetes do cliente — agrupa por sorteio com número visual formatado
        $bilhetesDoCliente = SorteioBilhete::where('cliente_id', $cliente->id)
            ->whereIn('sorteio_id', $sorteios->pluck('id'))
            ->orderBy('numero')
            ->get(['id', 'sorteio_id', 'numero'])
            ->groupBy('sorteio_id');

        return response()->json([
            'sorteios' => $sorteios->map(fn (Sorteio $s) => [
                'id'             => $s->id,
                'nome'           => $s->nome,
                'descricao'      => $s->descricao,
                'imagem'         => $s->imagem ? asset('storage/'.$s->imagem) : null,
                'data_sorteio'   => $s->data_sorteio->format('d/m/Y'),
                'status'         => $s->status,
                'recompensa'     => $s->recompensa?->nome,
                'valor_estimado' => $s->valor_estimado ? (float) $s->valor_estimado : null,
                'meus_bilhetes'  => $bilhetesDoCliente->has($s->id) ? $bilhetesDoCliente[$s->id]->count() : 0,
                'meus_numeros'   => $bilhetesDoCliente->has($s->id)
                    ? $bilhetesDoCliente[$s->id]->map(fn ($b) => '#'.str_pad((string) $b->numero, 4, '0', STR_PAD_LEFT))->all()
                    : [],
                'limite'         => $s->max_bilhetes_por_cliente,
                'vencedor'       => $s->vencedor?->nome,
                'vencedor_bilhete' => $s->vencedorBilhete?->numeroFormatado(),
                'eu_venci'       => $s->vencedor_cliente_id === $cliente->id,
            ])->values(),
            'total_bilhetes_ativos' => (int) SorteioBilhete::where('cliente_id', $cliente->id)
                ->whereHas('sorteio', fn ($q) => $q->where('empresa_id', $cliente->empresa_id)->where('status', 'ativo'))
                ->count(),
            'tem_historico' => SorteioBilhete::where('cliente_id', $cliente->id)
                ->whereHas('sorteio', fn ($q) => $q->where('empresa_id', $cliente->empresa_id)->whereIn('status', ['finalizado', 'cancelado']))
                ->exists(),
        ]);
    }
}
