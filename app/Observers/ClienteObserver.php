<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\Roleta;
use App\Services\ProcessarGatilhosRoletaService;
use Throwable;

class ClienteObserver
{
    public function __construct(private ProcessarGatilhosRoletaService $gatilhos) {}

    /**
     * Mantém `telefone_digits` sincronizado com `telefone`. Roda em
     * `creating` e `updating` antes do save, pra que a coluna esteja
     * preenchida quando o INSERT/UPDATE atingir o banco.
     */
    public function creating(Cliente $cliente): void
    {
        $this->sincronizarTelefoneDigits($cliente);
    }

    public function updating(Cliente $cliente): void
    {
        if ($cliente->isDirty('telefone')) {
            $this->sincronizarTelefoneDigits($cliente);
        }
    }

    protected function sincronizarTelefoneDigits(Cliente $cliente): void
    {
        $cliente->telefone_digits = $cliente->telefone
            ? preg_replace('/\D/', '', $cliente->telefone)
            : null;
    }

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
