<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesquisa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvaliacaoController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $query = Pesquisa::where('empresa_id', $empresaId)->with('cliente:id,nome,telefone');

        if ($filtro = $request->input('nota')) {
            if ($filtro === 'promotores') {
                $query->where('nota', '>=', 4);
            } elseif ($filtro === 'neutros') {
                $query->where('nota', 3);
            } elseif ($filtro === 'detratores') {
                $query->where('nota', '<=', 2);
            } elseif (is_numeric($filtro) && $filtro >= 1 && $filtro <= 5) {
                $query->where('nota', (int) $filtro);
            }
        }

        $busca = trim((string) $request->input('busca', ''));
        if (mb_strlen($busca) >= 2) {
            $query->whereHas('cliente', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('telefone', 'like', "%{$busca}%");
            });
        }

        $avaliacoes = $query->latest()->paginate(10)->withQueryString();

        // Métricas resumo
        $base = Pesquisa::where('empresa_id', $empresaId);
        $resumo = [
            'total'      => (clone $base)->count(),
            'media'      => round((clone $base)->avg('nota') ?? 0, 2),
            'promotores' => (clone $base)->where('nota', '>=', 4)->count(),
            'neutros'    => (clone $base)->where('nota', 3)->count(),
            'detratores' => (clone $base)->where('nota', '<=', 2)->count(),
        ];

        return view('admin.avaliacoes.index', compact('avaliacoes', 'resumo'));
    }

    public function destroy(Pesquisa $avaliacao)
    {
        abort_if($avaliacao->empresa_id !== Auth::user()->empresa_id, 403);
        $avaliacao->delete();
        return back()->with('success', 'Avaliação removida.');
    }
}
