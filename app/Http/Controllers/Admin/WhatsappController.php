<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public function edit()
    {
        $empresa = Auth::user()->empresa;

        // Garante que tenha um verify_token (caso a empresa seja antiga e a
        // migration não tenha pegado, ou alguém tenha apagado).
        if (empty($empresa->whatsapp_webhook_verify_token)) {
            $empresa->update(['whatsapp_webhook_verify_token' => 'wh_'.Str::random(32)]);
        }

        return view('admin.whatsapp.edit', compact('empresa'));
    }

    public function regenerarWebhookToken()
    {
        $empresa = Auth::user()->empresa;
        $empresa->update(['whatsapp_webhook_verify_token' => 'wh_'.Str::random(32)]);
        return back()->with('success', 'Novo token gerado. Atualize também no painel da Meta.');
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
