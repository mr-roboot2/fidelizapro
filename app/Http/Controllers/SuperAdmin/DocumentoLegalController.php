<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DocumentoLegal;
use Illuminate\Http\Request;

class DocumentoLegalController extends Controller
{
    public function index()
    {
        $documentos = DocumentoLegal::orderBy('slug')->get();
        return view('super.documentos.index', compact('documentos'));
    }

    public function edit(string $slug)
    {
        $documento = DocumentoLegal::where('slug', $slug)->firstOrFail();
        return view('super.documentos.edit', compact('documento'));
    }

    public function update(Request $request, string $slug)
    {
        $documento = DocumentoLegal::where('slug', $slug)->firstOrFail();

        $dados = $request->validate([
            'titulo'   => 'required|string|max:255',
            'conteudo' => 'required|string',
        ]);

        $documento->update($dados);

        return redirect()->route('super.documentos.index')
            ->with('success', "Documento \"{$documento->titulo}\" atualizado.");
    }
}
