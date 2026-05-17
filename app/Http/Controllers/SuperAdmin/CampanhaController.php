<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Campanha;
use App\Models\Empresa;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class CampanhaController extends Controller
{
    public function index()
    {
        $campanhas = Campanha::with('empresa:id,nome')->latest()->paginate(15);
        return view('super.campanhas.index', compact('campanhas'));
    }

    public function create()
    {
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        return view('super.campanhas.form', [
            'campanha' => new Campanha(),
            'empresas' => $empresas,
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['status'] = $request->input('status', 'rascunho');
        Campanha::create($dados);
        return redirect()->route('super.campanhas.index')->with('success', 'Campanha criada!');
    }

    public function edit(Campanha $campanha)
    {
        $empresas = Empresa::orderBy('nome')->get(['id', 'nome']);
        return view('super.campanhas.form', compact('campanha', 'empresas'));
    }

    public function update(Request $request, Campanha $campanha)
    {
        $dados = $this->validar($request);
        $campanha->update($dados);
        return redirect()->route('super.campanhas.index')->with('success', 'Campanha atualizada!');
    }

    public function disparar(Campanha $campanha, WhatsappService $service)
    {
        // Bloqueia re-disparo: campanha já enviada não pode ser disparada
        // de novo (super clicava 2x e duplicava mensagens pra base toda).
        // Status 'rascunho'/'agendada' permitido — 'enviada'/'cancelada'
        // não. Use Campanha::lockForUpdate pra fechar race entre 2 cliques
        // simultâneos.
        $atualizada = \Illuminate\Support\Facades\DB::transaction(function () use ($campanha) {
            $lockada = Campanha::lockForUpdate()->find($campanha->id);
            if (!$lockada || in_array($lockada->status, ['enviada', 'cancelada'], true)) {
                return null;
            }
            // Marca como "enviando" pra serializar; o service pode levar
            // tempo (Whatsapp HTTP por cliente). Após o disparo completar,
            // o próprio service atualiza pra 'enviada'.
            $lockada->update(['status' => 'enviada', 'disparada_em' => now()]);
            return $lockada;
        });

        if (!$atualizada) {
            return back()->with('error', 'Esta campanha já foi disparada ou cancelada.');
        }

        try {
            $service->dispararCampanha($atualizada);
        } catch (\Throwable $e) {
            // Falha do gateway/serviço — devolve a campanha pra rascunho
            // pra super tentar de novo. Sem isso, status='enviada' impedia
            // retry após erro de rede.
            $atualizada->update(['status' => 'rascunho', 'disparada_em' => null]);
            throw $e;
        }

        return back()->with('success', 'Campanha disparada! Acompanhe os envios.');
    }

    public function destroy(Campanha $campanha)
    {
        $campanha->delete();
        return redirect()->route('super.campanhas.index')->with('success', 'Campanha removida.');
    }

    protected function validar(Request $request): array
    {
        // agendada_para removido do form: campo existe no schema mas
        // nenhum job roda agendamentos. Antes a validação aceitava data
        // futura e o sistema engolia silenciosamente — UX enganosa.
        // Quando implementar agendador, devolver o campo com explicação.
        return $request->validate([
            'empresa_id' => 'nullable|exists:empresas,id',
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string',
            'canal' => 'required|in:whatsapp,sms,email',
            'segmento' => 'required|in:todos,aniversariantes,inativos,vips,sem_compra_30d,personalizado',
        ]);
    }
}
