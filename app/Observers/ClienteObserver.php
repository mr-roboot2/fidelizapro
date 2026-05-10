<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Roleta;
use App\Services\RoletaService;
use Throwable;

class ClienteObserver
{
    public function __construct(private RoletaService $roletaService) {}

    public function created(Cliente $cliente): void
    {
        try {
            $roleta = Roleta::where('empresa_id', $cliente->empresa_id)
                ->where('ativa', true)
                ->first();
            if ($roleta) {
                $this->roletaService->creditar($roleta, $cliente, 1, 'primeiro_cadastro');
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
