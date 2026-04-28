<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resgate;
use App\Services\ResgateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResgateController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = Resgate::with(['cliente', 'recompensa'])->where('empresa_id', $empresaId);

        if ($status = $request->input('status')) $query->where('status', $status);

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
            $service->entregar($resgate);
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

    protected function autorizar(Resgate $resgate): void
    {
        abort_if($resgate->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
