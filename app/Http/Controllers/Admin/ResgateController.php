<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resgate;
use App\Services\ResgateService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ResgateController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = Resgate::with(['cliente', 'recompensa'])->where('empresa_id', $empresaId);

        if ($status = $request->input('status')) $query->where('status', $status);

        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) >= 2) {
            $query->where(function ($w) use ($q) {
                $w->where('codigo', 'like', "%{$q}%")
                  ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', "%{$q}%")
                                                     ->orWhere('telefone', 'like', "%{$q}%")
                                                     ->orWhere('cpf', 'like', "%{$q}%"))
                  ->orWhereHas('recompensa', fn($c) => $c->where('nome', 'like', "%{$q}%"));
            });
        }

        $resgates = $query->latest()->paginate(20)->withQueryString();
        return view('admin.resgates.index', compact('resgates'));
    }

    public function show(Resgate $resgate)
    {
        $this->autorizar($resgate);
        $resgate->load('cliente', 'recompensa', 'aprovador');
        return view('admin.resgates.show', compact('resgate'));
    }

    public function aprovar(Resgate $resgate, ResgateService $service)
    {
        $this->autorizar($resgate);
        try {
            $service->aprovar($resgate, Auth::user());
            return back()->with('success', 'Resgate aprovado!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function entregar(Resgate $resgate, ResgateService $service)
    {
        $this->autorizar($resgate);
        try {
            $service->entregar($resgate, Auth::user());
            return back()->with('success', 'Resgate marcado como entregue!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelar(Request $request, Resgate $resgate, ResgateService $service)
    {
        $this->autorizar($resgate);
        try {
            $service->cancelar($resgate, $request->input('motivo'));
            return back()->with('success', 'Resgate cancelado e pontos estornados.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Relatório imprimível com auditoria — cliente, recompensa, pontos,
     * quem aprovou, quem entregou, datas, IP, observações. Filtra por
     * período e status.
     */
    public function relatorio(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $empresa = Auth::user()->empresa;

        $de = $request->filled('de')
            ? Carbon::parse($request->input('de'))->startOfDay()
            : now()->subDays(30)->startOfDay();
        $ate = $request->filled('ate')
            ? Carbon::parse($request->input('ate'))->endOfDay()
            : now()->endOfDay();
        if ($ate->lt($de)) [$de, $ate] = [$ate, $de];

        $query = Resgate::with(['cliente', 'recompensa', 'aprovador', 'entregador'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$de, $ate]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $resgates = $query->orderBy('created_at', 'desc')->get();

        $stats = [
            'total'        => $resgates->count(),
            'entregues'    => $resgates->where('status', 'entregue')->count(),
            'pendentes'    => $resgates->where('status', 'pendente')->count(),
            'aprovados'    => $resgates->where('status', 'aprovado')->count(),
            'cancelados'   => $resgates->where('status', 'cancelado')->count(),
            'pontos_total' => (float) $resgates->sum('pontos_usados'),
        ];

        return view('admin.resgates.relatorio', compact('resgates', 'stats', 'de', 'ate', 'empresa', 'request'));
    }

    protected function autorizar(Resgate $resgate): void
    {
        abort_if($resgate->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
