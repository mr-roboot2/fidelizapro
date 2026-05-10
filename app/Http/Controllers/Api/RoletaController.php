<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoletaService;
use DomainException;
use Illuminate\Http\Request;

class RoletaController extends Controller
{
    public function __construct(private RoletaService $roletaService) {}

    public function status(Request $request)
    {
        return response()->json($this->roletaService->statusParaCliente($request->user()));
    }

    public function girar(Request $request)
    {
        try {
            $resultado = $this->roletaService->girar($request->user(), $request->ip());
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($resultado);
    }
}
