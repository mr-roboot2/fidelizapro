<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Generator as QrGenerator;

class PwaShareController extends Controller
{
    public function index()
    {
        $empresa = Auth::user()->empresa;
        $url = url('/app/'.$empresa->slug.'/');
        $qrSvg = (new QrGenerator())->format('svg')->size(360)->margin(2)->generate($url);
        return view('admin.pwa.share', compact('empresa', 'url', 'qrSvg'));
    }

    /**
     * Cartaz dedicado pra impressão (sem sidebar/header). Tela limpa com
     * QR grande, nome da empresa em destaque e instruções pro cliente.
     */
    public function cartaz()
    {
        $empresa = Auth::user()->empresa;
        $url = url('/app/'.$empresa->slug.'/');
        $qrSvg = (new QrGenerator())->format('svg')->size(520)->margin(1)->generate($url);
        return view('admin.pwa.cartaz', compact('empresa', 'url', 'qrSvg'));
    }
}
