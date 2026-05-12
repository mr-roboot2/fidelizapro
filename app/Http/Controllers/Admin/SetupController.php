<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetupController extends Controller
{
    public function index(SetupService $service)
    {
        $empresa = Auth::user()->empresa;
        $resumo = $service->resumo($empresa);
        return view('admin.setup.index', compact('empresa', 'resumo'));
    }

    /**
     * "Pular configuração" — marca setup_concluido=true. O wizard some
     * mas continua acessível em /admin/setup.
     */
    public function pular(Request $request)
    {
        $empresa = Auth::user()->empresa;
        $empresa->update(['setup_concluido' => true]);
        return redirect()->route('admin.dashboard')
            ->with('success', 'Setup pulado. Você pode revisar a qualquer momento em /admin/setup.');
    }

    /**
     * Reabrir o wizard (limpa flag).
     */
    public function reabrir()
    {
        $empresa = Auth::user()->empresa;
        $empresa->update(['setup_concluido' => false]);
        return redirect()->route('admin.setup.index');
    }
}
