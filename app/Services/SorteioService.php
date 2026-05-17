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
    public function criarBilhete(Sorteio $sorteio, Cliente $cliente, string $origem = 'manual', ?string $referencia = null, ?string $ip = null): ?SorteioBilhete
    {
        // Cross-tenant: cliente de outra empresa NÃO pode receber bilhete.
        // Antes o service não validava — defesa estava só no controller,
        // mas o service é chamado de múltiplos pontos (roleta_premio
        // tipo=sorteio_bilhete, importação, jobs futuros). Cliente
        // desativado também não recebe (sorteio termina entregando pra
        // ninguém — UX confusa).
        if ($cliente->empresa_id !== $sorteio->empresa_id) {
            throw new \DomainException('Cliente não pertence à empresa do sorteio.');
        }
        if (!$cliente->ativo) {
            return null;
        }

        if (!$sorteio->aceitaBilhetes()) {
            return null;
        }

        if ($sorteio->max_bilhetes_por_cliente) {
            $jaTem = $sorteio->bilhetes()->where('cliente_id', $cliente->id)->count();
            if ($jaTem >= $sorteio->max_bilhetes_por_cliente) {
                return null;
            }
        }

        // Antifraude por IP: limite de bilhetes únicos do mesmo IP no dia
        if ($ip && $sorteio->limite_bilhetes_dia_por_ip) {
            $doMesmoIp = SorteioBilhete::where('sorteio_id', $sorteio->id)
                ->where('ip', $ip)
                ->whereDate('created_at', now()->toDateString())
                ->count();
            if ($doMesmoIp >= $sorteio->limite_bilhetes_dia_por_ip) {
                report(new \RuntimeException("Bilhete bloqueado por antifraude IP={$ip} sorteio={$sorteio->id} cliente={$cliente->id}"));
                return null;
            }
        }

        $bilhete = DB::transaction(function () use ($sorteio, $cliente, $origem, $referencia, $ip) {
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
                'ip'         => $ip,
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
        return DB::transaction(function () use ($sorteio) {
            // Lock + recheck DENTRO da transaction. Admin clicando 2x rápido
            // disparava 2 sorteios paralelos: ambos passavam o check de
            // status fora da transaction, ambos pegavam bilhetes aleatórios
            // diferentes, ambos faziam update do sorteio (último vencia) e
            // ambos chamavam concederPremio → 2 Resgates criados.
            $sorteio = Sorteio::lockForUpdate()->findOrFail($sorteio->id);

            if ($sorteio->status === 'sorteado') {
                return $sorteio;
            }
            if ($sorteio->status === 'cancelado') {
                throw new DomainException('Sorteio cancelado não pode ser sorteado.');
            }

            // Filtra clientes desativados na seleção do bilhete vencedor.
            // Sem isso o sorteio podia premiar cliente inativo e criar
            // Resgate que não dava pra entregar (Resgate::entregar valida
            // expira_em mas não checa cliente.ativo). Quando todos os
            // bilhetes são de inativos, cai no DomainException abaixo.
            $bilhete = SorteioBilhete::where('sorteio_id', $sorteio->id)
                ->whereHas('cliente', fn ($q) => $q->where('ativo', true))
                ->inRandomOrder()
                ->lockForUpdate()
                ->first();

            if (!$bilhete) {
                throw new DomainException('Sorteio sem bilhetes elegíveis — ninguém pra sortear (verifique se há clientes ativos com bilhetes).');
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
