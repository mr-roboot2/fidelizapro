<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConfiguracaoController extends Controller
{
    public function edit()
    {
        $empresa = Auth::user()->empresa;
        return view('admin.configuracoes.edit', compact('empresa'));
    }

    public function update(Request $request)
    {
        $empresa = Auth::user()->empresa;
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'endereco' => 'nullable|string|max:255',
            'cor_primaria' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'cor_secundaria' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            // max:100 cap defensivo igual ao CadastroEmpresaController — admin
            // não deve setar 999999 pontos por R$1 (inflação descontrolada).
            'pontos_por_real' => 'required|numeric|min:0|max:100',
            'cashback_percentual' => 'required|numeric|min:0|max:100',
            'dias_liberar_cashback' => 'required|integer|min:0|max:365',
            'validade_pontos_dias' => 'required|integer|min:30',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|mimetypes:image/png,image/jpeg,image/webp|max:1024',
        ]);

        if ($request->hasFile('logo')) {
            if ($empresa->logo) Storage::disk('public')->delete($empresa->logo);
            $dados['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $empresa->update($dados);
        return back()->with('success', 'Configurações salvas!');
    }
}
