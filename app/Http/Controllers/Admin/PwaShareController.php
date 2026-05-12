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
}
