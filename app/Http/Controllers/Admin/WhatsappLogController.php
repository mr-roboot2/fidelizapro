<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappEnvio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappLogController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $query = WhatsappEnvio::with(['cliente:id,nome,telefone'])
            ->where('empresa_id', $empresaId);

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

        if ($evento = $request->input('evento')) {
            $query->where('evento', $evento);
        }

        if ($origem = $request->input('origem')) {
            $query->where('origem', $origem);
        }

        $envios = $query->latest('created_at')->paginate(30)->withQueryString();

        $eventos = WhatsappEnvio::where('empresa_id', $empresaId)
            ->distinct()->pluck('evento')->filter()->sort()->values();
        $origens = WhatsappEnvio::where('empresa_id', $empresaId)
            ->distinct()->pluck('origem')->filter()->sort()->values();

        $resumo = [
            'total'  => WhatsappEnvio::where('empresa_id', $empresaId)->count(),
            'hoje'   => WhatsappEnvio::where('empresa_id', $empresaId)->whereDate('created_at', today())->count(),
            'falhas' => WhatsappEnvio::where('empresa_id', $empresaId)
                ->where('sucesso', false)
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return view('admin.whatsapp-logs.index', compact('envios', 'eventos', 'origens', 'resumo'));
    }
}
