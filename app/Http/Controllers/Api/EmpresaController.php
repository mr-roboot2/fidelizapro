<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;

class EmpresaController extends Controller
{
    public function publicas()
    {
        $empresas = Empresa::where('ativo', true)->get(['id', 'slug', 'nome', 'logo', 'cor_primaria']);
        return response()->json([
            'empresas' => $empresas->map(fn($e) => [
                'slug' => $e->slug,
                'nome' => $e->nome,
                'logo' => $e->logo ? asset('storage/'.$e->logo) : null,
                'cor_primaria' => $e->cor_primaria,
            ]),
        ]);
    }
}
