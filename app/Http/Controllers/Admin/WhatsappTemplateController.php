<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $configurados = WhatsappTemplate::where('empresa_id', $empresaId)
            ->get()->keyBy('evento');

        return view('admin.whatsapp-templates.index', [
            'eventos'      => WhatsappTemplate::EVENTOS,
            'configurados' => $configurados,
        ]);
    }

    /**
     * Lista os templates disponíveis na WABA via API da Meta — debug
     * útil quando o nome/idioma cadastrado aqui não bate com o que a
     * Meta tem registrado.
     */
    public function listarMeta()
    {
        $empresa = Auth::user()->empresa;

        if ($empresa->whatsapp_provider !== 'meta_cloud' || !$empresa->whatsapp_api_token) {
            return back()->with('error', 'Só funciona com provedor Meta Cloud configurado.');
        }

        if (!$empresa->whatsapp_waba_id) {
            return back()->with('error', 'Cadastre o WABA ID em /admin/whatsapp primeiro. Encontre no painel Meta → WhatsApp → API setup → "WhatsApp Business Account ID".');
        }

        try {
            $templates = Http::withToken($empresa->whatsapp_api_token)
                ->timeout(15)
                ->get("https://graph.facebook.com/v18.0/{$empresa->whatsapp_waba_id}/message_templates", [
                    'fields' => 'name,status,language,category',
                    'limit'  => 100,
                ]);

            if (!$templates->successful()) {
                return back()->with('error', 'Falha ao listar templates: '.($templates->json('error.message') ?? $templates->body()));
            }

            return view('admin.whatsapp-templates.meta', [
                'templates' => $templates->json('data', []),
                'waba_id'   => $empresa->whatsapp_waba_id,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro: '.$e->getMessage());
        }
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

        $empresaId = Auth::user()->empresa_id;

        if (empty($dados['nome_template'])) {
            // Limpar template — volta a usar texto livre
            WhatsappTemplate::where('empresa_id', $empresaId)->where('evento', $evento)->delete();
            return back()->with('success', 'Template removido — esse evento volta a enviar texto livre.');
        }

        WhatsappTemplate::updateOrCreate(
            ['empresa_id' => $empresaId, 'evento' => $evento],
            [
                'nome_template' => $dados['nome_template'],
                'idioma'        => $dados['idioma'],
                'ativo'         => $request->boolean('ativo', true),
            ]
        );

        return back()->with('success', 'Template salvo.');
    }
}
