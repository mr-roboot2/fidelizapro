<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Roleta;
use App\Services\ProcessarGatilhosRoletaService;
use Throwable;

class ClienteObserver
{
    public function __construct(private ProcessarGatilhosRoletaService $gatilhos) {}

    public function created(Cliente $cliente): void
    {
        try {
            $roleta = Roleta::where('empresa_id', $cliente->empresa_id)
                ->where('ativa', true)
                ->with(['gatilhos' => fn ($q) => $q->where('tipo', 'primeiro_cadastro')->where('ativo', true)])
                ->first();

            if (!$roleta) return;

            $g = $roleta->gatilhos->first();
            $giros = $g ? $g->giros : 1;
            $this->gatilhos->disparar($roleta, $cliente, 'primeiro_cadastro', 'primeiro_cadastro:'.$cliente->id, $giros);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
