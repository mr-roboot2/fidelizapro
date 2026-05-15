<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Illuminate\Http\Request;

class AjudaController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->input('busca', ''));

        $query = Tutorial::publicados()->ordenados();
        if (mb_strlen($busca) >= 2) {
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                  ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        $tutoriais = $query->get();

        return view('admin.ajuda.index', compact('tutoriais', 'busca'));
    }
}
