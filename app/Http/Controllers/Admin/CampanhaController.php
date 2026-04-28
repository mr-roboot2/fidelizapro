<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campanha;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampanhaController extends Controller
{
    public function index()
    {
        $campanhas = Campanha::where('empresa_id', Auth::user()->empresa_id)
            ->latest()->paginate(15);
        return view('admin.campanhas.index', compact('campanhas'));
    }

    public function create()
    {
        return view('admin.campanhas.form', ['campanha' => new Campanha()]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['empresa_id'] = Auth::user()->empresa_id;
        $dados['status'] = $request->input('status', 'rascunho');
        Campanha::create($dados);
        return redirect()->route('admin.campanhas.index')->with('success', 'Campanha criada!');
    }

    public function edit(Campanha $campanha)
    {
        $this->autorizar($campanha);
        return view('admin.campanhas.form', compact('campanha'));
    }

    public function update(Request $request, Campanha $campanha)
    {
        $this->autorizar($campanha);
        $dados = $this->validar($request);
        $campanha->update($dados);
        return redirect()->route('admin.campanhas.index')->with('success', 'Campanha atualizada!');
    }

    public function disparar(Campanha $campanha, WhatsappService $service)
    {
        $this->autorizar($campanha);
        $service->dispararCampanha($campanha);
        return back()->with('success', 'Campanha disparada! Acompanhe os envios.');
    }

    public function destroy(Campanha $campanha)
    {
        $this->autorizar($campanha);
        $campanha->delete();
        return redirect()->route('admin.campanhas.index')->with('success', 'Campanha removida.');
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'mensagem' => 'required|string',
            'canal' => 'required|in:whatsapp,sms,email',
            'segmento' => 'required|in:todos,aniversariantes,inativos,vips,sem_compra_30d,personalizado',
            'agendada_para' => 'nullable|date|after:now',
        ]);
    }

    protected function autorizar(Campanha $campanha): void
    {
        abort_if($campanha->empresa_id !== Auth::user()->empresa_id, 403);
    }
}
