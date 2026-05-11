<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Resgate;
use App\Models\Sorteio;
use App\Models\SorteioBilhete;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class SorteioService
{
    public function __construct(private WhatsappService $whatsapp) {}

    /**
     * Cria 1 bilhete pro cliente no sorteio. Respeita max_bilhetes_por_cliente.
     * Retorna o bilhete ou null se o limite foi atingido (silencioso).
     */
    public function criarBilhete(Sorteio $sorteio, Cliente $cliente, string $origem = 'manual', ?string $referencia = null): ?SorteioBilhete
    {
        if (!$sorteio->aceitaBilhetes()) {
            return null;
        }

        if ($sorteio->max_bilhetes_por_cliente) {
            $jaTem = $sorteio->bilhetes()->where('cliente_id', $cliente->id)->count();
            if ($jaTem >= $sorteio->max_bilhetes_por_cliente) {
                return null;
            }
        }

        $bilhete = DB::transaction(function () use ($sorteio, $cliente, $origem, $referencia) {
            // Calcula próximo número sequencial DENTRO do sorteio, com lock pra
            // evitar race condition. A unique(sorteio_id, numero) é a garantia final.
            $ultimo = SorteioBilhete::where('sorteio_id', $sorteio->id)
                ->lockForUpdate()
                ->max('numero');
            return $sorteio->bilhetes()->create([
                'cliente_id' => $cliente->id,
                'numero'     => ($ultimo ?? 0) + 1,
                'origem'     => $origem,
                'referencia' => $referencia,
                'created_at' => now(),
            ]);
        });

        $this->notificarBilhete($sorteio, $cliente);

        return $bilhete;
    }

    /**
     * Sortear: pega 1 bilhete aleatório, marca vencedor. Idempotente —
     * sorteio já sorteado retorna o vencedor existente.
     */
    public function sortear(Sorteio $sorteio): Sorteio
    {
        if ($sorteio->status === 'sorteado') {
            return $sorteio;
        }
        if ($sorteio->status === 'cancelado') {
            throw new DomainException('Sorteio cancelado não pode ser sorteado.');
        }

        return DB::transaction(function () use ($sorteio) {
            $bilhete = SorteioBilhete::where('sorteio_id', $sorteio->id)
                ->inRandomOrder()
                ->lockForUpdate()
                ->first();

            if (!$bilhete) {
                throw new DomainException('Sorteio sem bilhetes — ninguém pra sortear.');
            }

            $vencedor = Cliente::findOrFail($bilhete->cliente_id);
            $sorteio->update([
                'status'              => 'sorteado',
                'vencedor_cliente_id' => $vencedor->id,
                'vencedor_bilhete_id' => $bilhete->id,
                'sorteado_em'         => now(),
            ]);

            $this->concederPremio($sorteio, $vencedor);
            $this->notificarVencedor($sorteio, $vencedor);

            return $sorteio;
        });
    }

    /**
     * Quando o sorteio tem recompensa_id, cria um Resgate aprovado pro
     * vencedor (mesmo padrão do prêmio de roleta).
     */
    private function concederPremio(Sorteio $sorteio, Cliente $vencedor): void
    {
        if (!$sorteio->recompensa_id) return;

        Resgate::create([
            'empresa_id'    => $sorteio->empresa_id,
            'cliente_id'    => $vencedor->id,
            'recompensa_id' => $sorteio->recompensa_id,
            'pontos_usados' => 0,
            'status'        => 'aprovado',
            'observacao'    => "Vencedor do sorteio: {$sorteio->nome}",
            'aprovado_em'   => now(),
        ]);
    }

    private function notificarBilhete(Sorteio $sorteio, Cliente $cliente): void
    {
        if (!$cliente->aceita_whatsapp || !$cliente->telefone) return;
        try {
            $this->whatsapp->enviarEvento(
                $cliente->empresa,
                $cliente->telefone,
                'sorteio_bilhete_ganho',
                [
                    explode(' ', $cliente->nome)[0],
                    $sorteio->nome,
                    $sorteio->data_sorteio->format('d/m/Y'),
                ],
                origem: 'sorteio'
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function notificarVencedor(Sorteio $sorteio, Cliente $vencedor): void
    {
        if (!$vencedor->aceita_whatsapp || !$vencedor->telefone) return;
        try {
            $this->whatsapp->enviarEvento(
                $vencedor->empresa,
                $vencedor->telefone,
                'sorteio_vencedor',
                [
                    explode(' ', $vencedor->nome)[0],
                    $sorteio->nome,
                ],
                origem: 'sorteio'
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
