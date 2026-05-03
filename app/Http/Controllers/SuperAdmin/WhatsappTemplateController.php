<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSistema;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $configurados = WhatsappTemplate::all()->keyBy('evento');
        return view('super.whatsapp-templates.index', [
            'eventos'      => WhatsappTemplate::EVENTOS,
            'configurados' => $configurados,
        ]);
    }

    public function update(Request $request, string $evento)
    {
        if (!isset(WhatsappTemplate::EVENTOS[$evento])) {
            abort(404, 'Evento desconhecido.');
        }

        $dados = $request->validate([
            'nome_template' => 'nullable|string|max:120',
            'idioma'        => 'required|string|max:10',
            'ativo'         => 'boolean',
        ]);

        if (empty($dados['nome_template'])) {
            WhatsappTemplate::where('evento', $evento)->delete();
            return back()->with('success', 'Template removido — esse evento volta a enviar texto livre.');
        }

        WhatsappTemplate::updateOrCreate(
            ['evento' => $evento],
            [
                'nome_template' => $dados['nome_template'],
                'idioma'        => $dados['idioma'],
                'ativo'         => $request->boolean('ativo', true),
            ]
        );

        return back()->with('success', 'Template salvo.');
    }

    public function listarMeta()
    {
        $config = ConfiguracaoSistema::instancia();

        if ($config->whatsapp_provider !== 'meta_cloud' || !$config->whatsapp_api_token) {
            return back()->with('error', 'Só funciona com provedor Meta Cloud configurado.');
        }

        if (!$config->whatsapp_waba_id) {
            return back()->with('error', 'Cadastre o WABA ID em /super/whatsapp primeiro.');
        }

        try {
            $templates = Http::withToken($config->whatsapp_api_token)
                ->timeout(15)
                ->get("https://graph.facebook.com/v18.0/{$config->whatsapp_waba_id}/message_templates", [
                    'fields' => 'name,status,language,category',
                    'limit'  => 100,
                ]);

            if (!$templates->successful()) {
                return back()->with('error', 'Falha ao listar templates: '.($templates->json('error.message') ?? $templates->body()));
            }

            return view('super.whatsapp-templates.meta', [
                'templates' => $templates->json('data', []),
                'waba_id'   => $config->whatsapp_waba_id,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro: '.$e->getMessage());
        }
    }
}
