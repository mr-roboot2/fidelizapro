<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recompensa;
use Illuminate\Http\Request;

class RecompensaController extends Controller
{
    public function catalogo(Request $request)
    {
        $cliente = $request->user();
        $recompensas = Recompensa::where('empresa_id', $cliente->empresa_id)
            ->where('ativo', true)
            ->orderByDesc('destaque')
            ->orderBy('custo_pontos')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'nome' => $r->nome,
                'descricao' => $r->descricao,
                'imagem' => $r->imagem ? asset('storage/'.$r->imagem) : null,
                'custo_pontos' => $r->custo_pontos,
                'tipo' => $r->tipo,
                'valor_estimado' => (float) $r->valor_estimado,
                'destaque' => (bool) $r->destaque,
                'estoque' => $r->estoque,
                'disponivel' => $r->disponivel(),
                'pode_resgatar' => $r->disponivel() && $cliente->pontos_atual >= $r->custo_pontos,
            ]);

        return response()->json(['recompensas' => $recompensas]);
    }
}
