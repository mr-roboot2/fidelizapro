<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracaoSistemaController extends Controller
{
    public function edit()
    {
        $config = ConfiguracaoSistema::instancia();
        return view('super.configuracoes.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $config = ConfiguracaoSistema::instancia();

        $dados = $request->validate([
            'nome_sistema'     => 'required|string|max:60',
            'slogan'           => 'nullable|string|max:120',
            'email_suporte'    => 'nullable|email|max:120',
            'telefone_suporte' => 'nullable|string|max:30',
            'whatsapp_suporte' => 'nullable|string|max:30',
            'razao_social'     => 'nullable|string|max:120',
            'cnpj'             => 'nullable|string|max:20',
            'endereco'         => 'nullable|string|max:255',
            'cor_primaria'     => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'cor_secundaria'   => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'site_url'         => 'nullable|url|max:120',
            'rodape_html'      => 'nullable|string|max:1000',
            'logo'             => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:1024',
            'favicon'          => 'nullable|image|mimes:png,jpg,jpeg,svg,webp,ico|max:256',
            'remover_logo'     => 'nullable|in:1',
            'remover_favicon'  => 'nullable|in:1',
        ]);

        if ($request->boolean('remover_logo') && $config->logo) {
            Storage::disk('public')->delete($config->logo);
            $dados['logo'] = null;
        }
        if ($request->boolean('remover_favicon') && $config->favicon) {
            Storage::disk('public')->delete($config->favicon);
            $dados['favicon'] = null;
        }
        unset($dados['remover_logo'], $dados['remover_favicon']);

        if ($request->hasFile('logo')) {
            if ($config->logo) Storage::disk('public')->delete($config->logo);
            $dados['logo'] = $request->file('logo')->store('sistema', 'public');
        } else {
            unset($dados['logo']);
        }

        if ($request->hasFile('favicon')) {
            if ($config->favicon) Storage::disk('public')->delete($config->favicon);
            $dados['favicon'] = $request->file('favicon')->store('sistema', 'public');
        } else {
            unset($dados['favicon']);
        }

        $config->update($dados);
        ConfiguracaoSistema::limparCache();

        return redirect()->route('super.configuracoes.edit')
            ->with('success', 'Configurações atualizadas.');
    }
}
