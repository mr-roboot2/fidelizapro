<?php

namespace App\Http\Controllers;

use App\Models\DocumentoLegal;

use Illuminate\Http\Request;

class DocumentoLegalPublicoController extends Controller
{
    public function show(Request $request)
    {
        $slug = $request->route('slug');
        $documento = DocumentoLegal::where('slug', $slug)->firstOrFail();
        return view('publico.documento-legal', compact('documento'));
    }
}
