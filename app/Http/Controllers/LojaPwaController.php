<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;

class LojaPwaController extends Controller
{
    public function app()
    {
        $config = ConfiguracaoSistema::instancia();
        return view('loja.app', compact('config'));
    }

    public function manifest()
    {
        $config = ConfiguracaoSistema::instancia();

        $logo = $config->logoUrl() ?? asset('app/icons/icon.svg');
        $scopePath = rtrim(parse_url(url('/loja'), PHP_URL_PATH), '/').'/';

        return response()->json([
            'name'             => $config->nome_sistema.' — Loja',
            'short_name'       => $config->nome_sistema.' Loja',
            'description'      => 'PWA da loja para registrar vendas e ler QR de clientes',
            'start_url'        => $scopePath,
            'scope'            => $scopePath,
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#ffffff',
            'theme_color'      => $config->cor_primaria,
            'lang'             => 'pt-BR',
            'icons'            => [[
                'src'     => $logo,
                'sizes'   => 'any',
                'type'    => $config->logo ? 'image/'.pathinfo($config->logo, PATHINFO_EXTENSION) : 'image/svg+xml',
                'purpose' => 'any maskable',
            ]],
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
