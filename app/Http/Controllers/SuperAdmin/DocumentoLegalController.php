<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DocumentoLegal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentoLegalController extends Controller
{
    /**
     * Slugs reservados pelo sistema — não podem ser usados como URL pública
     * de documento porque colidiriam com rotas existentes.
     */
    protected array $slugsReservados = [
        'admin', 'super', 'api', 'app', 'install', 'login', 'logout',
        'storage', 'webhook', 'pagamento-mock', 'parceiro', 'up', 'vendor',
    ];

    public function index()
    {
        $documentos = DocumentoLegal::orderBy('slug')->get();
        return view('super.documentos.index', compact('documentos'));
    }

    public function create()
    {
        $documento = new DocumentoLegal(['titulo' => '', 'slug' => '', 'conteudo' => '']);
        return view('super.documentos.form', [
            'documento' => $documento,
            'modo' => 'criar',
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['conteudo'] = $this->sanitizarHtml($dados['conteudo']);
        $documento = DocumentoLegal::create($dados);

        return redirect()->route('super.documentos.index')
            ->with('success', "Página \"{$documento->titulo}\" criada em /{$documento->slug}");
    }

    public function edit(string $slug)
    {
        $documento = DocumentoLegal::where('slug', $slug)->firstOrFail();
        return view('super.documentos.form', [
            'documento' => $documento,
            'modo' => 'editar',
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $documento = DocumentoLegal::where('slug', $slug)->firstOrFail();
        $dados = $this->validar($request, $documento->id);
        $dados['conteudo'] = $this->sanitizarHtml($dados['conteudo']);

        $slugAnterior = $documento->slug;
        $documento->update($dados);

        $msg = "Documento \"{$documento->titulo}\" atualizado.";
        if ($slugAnterior !== $documento->slug) {
            $msg .= " URL alterada: /{$slugAnterior} → /{$documento->slug}";
        }

        return redirect()->route('super.documentos.index')->with('success', $msg);
    }

    public function destroy(string $slug)
    {
        $documento = DocumentoLegal::where('slug', $slug)->firstOrFail();
        $titulo = $documento->titulo;
        $documento->delete();

        return redirect()->route('super.documentos.index')
            ->with('success', "Página \"{$titulo}\" excluída.");
    }

    protected function validar(Request $request, ?int $ignoreId = null): array
    {
        $reservados = $this->slugsReservados;

        return $request->validate([
            'titulo' => 'required|string|max:255',
            'slug'   => [
                'required', 'string', 'max:80',
                'regex:/^[a-z][a-z0-9-]*$/',
                Rule::unique('documentos_legais', 'slug')->ignore($ignoreId),
                function ($attr, $value, $fail) use ($reservados) {
                    if (in_array($value, $reservados, true)) {
                        $fail("O slug \"{$value}\" é reservado pelo sistema. Escolha outro.");
                    }
                },
            ],
            'conteudo' => 'required|string',
        ], [
            'slug.regex' => 'Use apenas letras minúsculas, números e hífens. Comece com letra.',
            'slug.unique' => 'Já existe outra página com esse slug.',
        ]);
    }

    /**
     * Limpa o HTML mantendo apenas tags estruturais inofensivas e neutraliza
     * `javascript:` / `data:` em href. Documento legal é exibido em rota
     * pública, então XSS aqui rouba sessão de qualquer visitante do site.
     */
    protected function sanitizarHtml(string $html): string
    {
        $tagsPermitidas = '<h1><h2><h3><h4><h5><h6>'
            .'<p><br><hr><div><span>'
            .'<strong><em><b><i><u><s><small><sub><sup>'
            .'<ul><ol><li>'
            .'<blockquote><pre><code>'
            .'<a>'
            .'<table><thead><tbody><tfoot><tr><th><td>';

        $limpo = strip_tags($html, $tagsPermitidas);

        // Bloqueia esquemas perigosos em href (javascript:, vbscript:, data:)
        $limpo = preg_replace_callback(
            '/href\s*=\s*("|\')([^"\']*)\1/i',
            function ($m) {
                $url = trim($m[2]);
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $url)) {
                    return 'href="#"';
                }
                return $m[0];
            },
            $limpo
        );

        // Remove event handlers inline (onclick=, onerror=, etc.)
        $limpo = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $limpo);

        return $limpo;
    }
}
