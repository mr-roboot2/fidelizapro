<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;

class EmpresaController extends Controller
{
    public function publicas()
    {
        $empresas = Empresa::where('ativo', true)->get(['id', 'slug', 'nome', 'logo', 'cor_primaria', 'cor_secundaria']);
        $sistema  = ConfiguracaoSistema::instancia();

        return response()->json([
            'sistema' => [
                'nome'           => $sistema->nome_sistema ?: 'FidelizaPro',
                'slogan'         => $sistema->slogan ?: 'Programa de fidelidade',
                'logo'           => $sistema->logoUrl(),
                'cor_primaria'   => $sistema->cor_primaria ?: '#6366f1',
                'cor_secundaria' => $sistema->cor_secundaria ?: '#8b5cf6',
            ],
            'empresas' => $empresas->map(fn($e) => [
                'slug' => $e->slug,
                'nome' => $e->nome,
                'logo' => $e->logo ? asset('storage/'.$e->logo) : null,
                'cor_primaria'   => $e->cor_primaria,
                'cor_secundaria' => $e->cor_secundaria,
            ]),
        ]);
    }
}
