<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Generator as QrGenerator;

class SetupController extends Controller
{
    public function index(SetupService $service)
    {
        $empresa = Auth::user()->empresa;
        $resumo = $service->resumo($empresa);
        return view('admin.setup.index', compact('empresa', 'resumo'));
    }

    /**
     * Tela com QR code e link do PWA pra colar na loja. Marca o passo
     * "pwa" como visualizado.
     */
    public function pwa()
    {
        $empresa = Auth::user()->empresa;
        $url = url('/app/'.$empresa->slug.'/');
        $qrSvg = (new QrGenerator())->format('svg')->size(360)->margin(2)->generate($url);
        return view('admin.setup.pwa', compact('empresa', 'url', 'qrSvg'));
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
