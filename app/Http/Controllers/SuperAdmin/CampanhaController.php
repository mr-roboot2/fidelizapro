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

    public function store(Request $request, \App\Services\PlanoLimiteService $limites)
    {
        $dados = $this->validar($request);
        $dados['status'] = $request->input('status', 'rascunho');

        // Limite mensal de campanhas do plano da empresa-alvo. Campanhas globais
        // (empresa_id=null = broadcast pra todas) não passam pelo limite — não
        // pertencem a nenhuma empresa. Conta o que JÁ existe no created_at do
        // mês corrente (mesma lógica do PlanoLimiteService::consumo).
        if (!empty($dados['empresa_id'])) {
            $empresa = \App\Models\Empresa::find($dados['empresa_id']);
            if ($empresa) {
                try {
                    $limites->garantirCapacidade($empresa, 'campanhas_mes');
                } catch (\DomainException $e) {
                    return back()->withInput()->with('error', $e->getMessage());
                }
            }
        }

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
        // Bloqueia re-disparo. Status do enum (migration
        // 2026_01_01_000008): rascunho, agendada, enviando, concluida,
        // falhou. Antes eu usava 'enviada'/'disparada_em' (ambos
        // inexistentes no schema) — o update silenciava em prod com
        // MariaDB lax mode e quebrava com strict. Alinhado pra
        // 'enviando' como flag de "em curso" e 'enviada_em' como
        // timestamp. WhatsappService::dispararCampanha já transiciona
        // 'enviando' → 'concluida' ao fim.
        $atualizada = \Illuminate\Support\Facades\DB::transaction(function () use ($campanha) {
            $lockada = Campanha::lockForUpdate()->find($campanha->id);
            if (!$lockada || in_array($lockada->status, ['enviando', 'concluida'], true)) {
                return null;
            }
            $lockada->update(['status' => 'enviando']);
            return $lockada;
        });

        if (!$atualizada) {
            return back()->with('error', 'Esta campanha já foi disparada (ou está em curso).');
        }

        try {
            $service->dispararCampanha($atualizada);
        } catch (\Throwable $e) {
            // Falha do gateway/serviço — devolve pra rascunho pra
            // super tentar de novo.
            $atualizada->update(['status' => 'rascunho']);
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
