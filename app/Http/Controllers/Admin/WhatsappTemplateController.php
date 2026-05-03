<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
