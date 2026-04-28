<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappController extends Controller
{
    public function edit()
    {
        $empresa = Auth::user()->empresa;
        return view('admin.whatsapp.edit', compact('empresa'));
    }

    public function update(Request $request)
    {
        $empresa = Auth::user()->empresa;

        $dados = $request->validate([
            'whatsapp_provider' => 'required|in:mock,evolution,zapi,meta_cloud',
            'whatsapp_api_url' => 'nullable|url|max:255',
            'whatsapp_api_token' => 'nullable|string|max:255',
            'whatsapp_instance' => 'nullable|string|max:255',
            'whatsapp_phone_id' => 'nullable|string|max:255',
            'whatsapp_ativo' => 'boolean',
        ]);

        $dados['whatsapp_ativo'] = $request->boolean('whatsapp_ativo');

        $empresa->update($dados);
        return back()->with('success', 'Configurações de WhatsApp salvas!');
    }

    public function testar(Request $request, WhatsappService $service)
    {
        $request->validate(['telefone_destino' => 'required|string']);

        $empresa = Auth::user()->empresa;
        $resultado = $service->testar($empresa, $request->input('telefone_destino'));

        return back()->with($resultado['ok'] ? 'success' : 'error', $resultado['mensagem']);
    }
}
