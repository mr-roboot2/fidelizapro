<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\WhatsappEnvio;
use Illuminate\Http\Request;

class WhatsappLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WhatsappEnvio::with(['empresa:id,nome', 'cliente:id,nome,telefone']);

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('telefone', 'like', "%{$q}%")
                  ->orWhere('mensagem', 'like', "%{$q}%")
                  ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', "%{$q}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('sucesso', $status === 'ok');
        }

        if ($empresaId = $request->input('empresa_id')) {
            $query->where('empresa_id', $empresaId);
        }

        if ($evento = $request->input('evento')) {
            $query->where('evento', $evento);
        }

        if ($origem = $request->input('origem')) {
            $query->where('origem', $origem);
        }

        $envios = $query->latest('created_at')->paginate(30)->withQueryString();

        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        // Listas finitas — antes faziam DISTINCT na tabela inteira (que
        // cresce sem teto, podendo ter 100k+ rows). DISTINCT em TEXT
        // sem índice fulltext é full scan. Listas constantes ou
        // consts do model resolvem.
        $eventos = collect(array_keys(\App\Models\WhatsappTemplate::EVENTOS ?? []));
        $origens = collect(['manual', 'automacao', 'campanha', 'otp', 'sistema', 'evento', 'resgate', 'sorteio']);

        $resumo = [
            'total'   => WhatsappEnvio::count(),
            'hoje'    => WhatsappEnvio::whereDate('created_at', today())->count(),
            'falhas'  => WhatsappEnvio::where('sucesso', false)->whereDate('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('super.whatsapp-logs.index', compact('envios', 'empresas', 'eventos', 'origens', 'resumo'));
    }
}
