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
        $service->dispararCampanha($campanha);
        return back()->with('success', 'Campanha disparada! Acompanhe os envios.');
    }

    public function destroy(Campanha $campanha)
    {
        $campanha->delete();
        return redirect()->route('super.campanhas.index')->with('success', 'Campanha removida.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'empresa_id' => 'nullable|exists:empresas,id',
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string',
            'canal' => 'required|in:whatsapp,sms,email',
            'segmento' => 'required|in:todos,aniversariantes,inativos,vips,sem_compra_30d,personalizado',
            'agendada_para' => 'nullable|date|after:now',
        ]);
    }
}
