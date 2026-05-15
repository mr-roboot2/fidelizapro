<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSistema;
use App\Rules\UrlExterna;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public function edit()
    {
        $config = ConfiguracaoSistema::instancia();

        if (empty($config->whatsapp_webhook_verify_token)) {
            $config->update(['whatsapp_webhook_verify_token' => 'wh_'.Str::random(32)]);
            ConfiguracaoSistema::limparCache();
            $config = ConfiguracaoSistema::instancia();
        }

        return view('super.whatsapp.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $dados = $request->validate([
            'whatsapp_provider' => 'required|in:mock,evolution,zapi,meta_cloud',
            // Anti-SSRF: bloqueia URL apontando pra metadata cloud (169.254.x),
            // loopback, IPs privados e hostnames .local/.internal. Sem isso,
            // super admin (ou conta dele comprometida) usa o sistema como
            // proxy SSRF pra exfiltrar creds da nuvem ou serviços internos.
            'whatsapp_api_url'  => ['nullable', 'url', 'max:255', new UrlExterna()],
            'whatsapp_api_token'=> 'nullable|string|max:2000',
            'whatsapp_client_token' => 'nullable|string|max:255',
            'whatsapp_instance' => 'nullable|string|max:255',
            'whatsapp_phone_id' => 'nullable|string|max:255',
            'whatsapp_waba_id'  => 'nullable|string|max:50',
            'whatsapp_ativo'    => 'boolean',
        ]);

        $dados['whatsapp_ativo'] = $request->boolean('whatsapp_ativo');

        $config = ConfiguracaoSistema::instancia();
        $config->update($dados);
        ConfiguracaoSistema::limparCache();

        return redirect()->route('super.whatsapp.edit')->with('success', 'Configurações de WhatsApp salvas!');
    }

    public function regenerarWebhookToken()
    {
        $config = ConfiguracaoSistema::instancia();
        $config->update(['whatsapp_webhook_verify_token' => 'wh_'.Str::random(32)]);
        ConfiguracaoSistema::limparCache();

        return back()->with('success', 'Novo token gerado. Atualize também no painel da Meta.');
    }

    public function testar(Request $request, WhatsappService $service)
    {
        $request->validate(['telefone_destino' => 'required|string']);

        $resultado = $service->testar($request->input('telefone_destino'));

        return back()->with($resultado['ok'] ? 'success' : 'error', $resultado['mensagem']);
    }
}
