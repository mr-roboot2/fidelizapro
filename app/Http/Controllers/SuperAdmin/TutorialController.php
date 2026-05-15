<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TutorialController extends Controller
{
    public function index()
    {
        $tutoriais = Tutorial::ordenados()->get();
        return view('super.tutoriais.index', compact('tutoriais'));
    }

    public function create()
    {
        $tutorial = new Tutorial([
            'tipo_video' => 'url',
            'publicado'  => true,
            'ordem'      => (int) Tutorial::max('ordem') + 10,
        ]);
        return view('super.tutoriais.form', [
            'tutorial' => $tutorial,
            'modo'     => 'criar',
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);

        if ($dados['tipo_video'] === 'upload' && $request->hasFile('video_arquivo')) {
            $dados['video_arquivo'] = $request->file('video_arquivo')
                ->store('tutoriais', 'public');
            $dados['video_url'] = null;
        } else {
            $dados['video_arquivo'] = null;
        }

        $tutorial = Tutorial::create($dados);

        return redirect()->route('super.tutoriais.index')
            ->with('success', "Tutorial \"{$tutorial->titulo}\" criado.");
    }

    public function edit(Tutorial $tutorial)
    {
        return view('super.tutoriais.form', [
            'tutorial' => $tutorial,
            'modo'     => 'editar',
        ]);
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        $dados = $this->validar($request, $tutorial);

        if ($dados['tipo_video'] === 'upload') {
            if ($request->hasFile('video_arquivo')) {
                // Remove arquivo anterior se houver
                if ($tutorial->video_arquivo) {
                    Storage::disk('public')->delete($tutorial->video_arquivo);
                }
                $dados['video_arquivo'] = $request->file('video_arquivo')
                    ->store('tutoriais', 'public');
            } else {
                // Mantém o arquivo atual
                $dados['video_arquivo'] = $tutorial->video_arquivo;
            }
            $dados['video_url'] = null;
        } else {
            // Trocou pra URL — limpa o arquivo antigo
            if ($tutorial->video_arquivo) {
                Storage::disk('public')->delete($tutorial->video_arquivo);
            }
            $dados['video_arquivo'] = null;
        }

        $tutorial->update($dados);

        return redirect()->route('super.tutoriais.index')
            ->with('success', "Tutorial \"{$tutorial->titulo}\" atualizado.");
    }

    public function destroy(Tutorial $tutorial)
    {
        $titulo = $tutorial->titulo;
        if ($tutorial->video_arquivo) {
            Storage::disk('public')->delete($tutorial->video_arquivo);
        }
        $tutorial->delete();

        return redirect()->route('super.tutoriais.index')
            ->with('success', "Tutorial \"{$titulo}\" excluído.");
    }

    public function toggle(Tutorial $tutorial)
    {
        $tutorial->update(['publicado' => !$tutorial->publicado]);
        $estado = $tutorial->publicado ? 'publicado' : 'despublicado';
        return back()->with('success', "Tutorial \"{$tutorial->titulo}\" {$estado}.");
    }

    public function reordenar(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        foreach ($request->input('ids') as $index => $id) {
            Tutorial::where('id', $id)->update(['ordem' => ($index + 1) * 10]);
        }
        return response()->json(['ok' => true]);
    }

    protected function validar(Request $request, ?Tutorial $tutorial = null): array
    {
        // Combina mimes (extensão) + mimetypes (header) — atacante teria que forjar os dois.
        // max:40960 (40MB) alinha com upload_max_filesize/post_max_size default do php.ini.
        // Antes era 200MB e qualquer upload entre 40-200MB caía com erro genérico do PHP
        // (parcial truncado no servidor). Pra suportar arquivos maiores, configure no
        // php.ini do servidor: upload_max_filesize, post_max_size, e em Nginx:
        // client_max_body_size.
        $regrasArquivo = ['nullable', 'file', 'mimes:mp4,webm,ogg,mov', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:40960'];

        // Se for criar com tipo=upload, exige o arquivo
        if ($request->input('tipo_video') === 'upload' && !$tutorial) {
            $regrasArquivo[0] = 'required';
        }

        return $request->validate([
            'titulo'        => 'required|string|max:255',
            'descricao'     => 'nullable|string|max:5000',
            'tipo_video'    => 'required|in:url,upload',
            'video_url'     => 'nullable|required_if:tipo_video,url|string|max:500|url',
            'video_arquivo' => $regrasArquivo,
            'duracao'       => 'nullable|string|max:20',
            'ordem'         => 'nullable|integer|min:0',
            'publicado'     => 'sometimes|boolean',
        ], [
            'video_url.required_if' => 'Cole a URL do YouTube/Vimeo.',
            'video_arquivo.required'  => 'Envie o arquivo de vídeo.',
            'video_arquivo.mimetypes' => 'Use MP4, WebM, OGG ou MOV.',
            'video_arquivo.max'       => 'Arquivo muito grande (máx. 200 MB).',
        ]);
    }
}
