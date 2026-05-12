<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Resgate;
use App\Models\Roleta;
use App\Models\RoletaCredito;
use App\Models\RoletaGiro;
use App\Models\RoletaPremio;
use App\Models\Sorteio;
use Illuminate\Support\Facades\DB;
use Throwable;

class RoletaService
{
    public function __construct(
        private PontuacaoService $pontuacao,
        private WhatsappService $whatsapp,
        private SorteioService $sorteioService,
    ) {}

    public function statusParaCliente(Cliente $cliente): array
    {
        $roleta = Roleta::where('empresa_id', $cliente->empresa_id)
            ->where('ativa', true)
            ->with(['premios' => fn ($q) => $q->where('ativo', true)])
            ->first();

        if (!$roleta) {
            return ['ativa' => false];
        }

        $credito = $this->creditoDoCliente($roleta, $cliente);
        $girosHoje = RoletaGiro::where('roleta_id', $roleta->id)
            ->where('cliente_id', $cliente->id)
            ->whereDate('executado_em', now()->toDateString())
            ->count();

        return [
            'ativa'             => true,
            'roleta_id'         => $roleta->id,
            'nome'              => $roleta->nome,
            'tempo_min_ms'      => $roleta->tempo_min_ms,
            'tempo_max_ms'      => $roleta->tempo_max_ms,
            'giros_disponiveis' => $credito?->valido() ? $credito->giros_disponiveis : 0,
            'giros_usados_hoje' => $girosHoje,
            'limite_giros_dia'  => $roleta->limite_giros_dia,
            'pode_girar'        => $this->podeGirar($roleta, $cliente, $credito, $girosHoje),
            'premios'           => $roleta->premios->map(fn (RoletaPremio $p) => [
                'id'    => $p->id,
                'ordem' => $p->ordem,
                'label' => $p->label,
                'cor'   => $p->cor,
                'tipo'  => $p->tipo,
            ])->values(),
        ];
    }

    public function girar(Cliente $cliente, ?string $ip = null): array
    {
        return DB::transaction(function () use ($cliente, $ip) {
            $roleta = Roleta::where('empresa_id', $cliente->empresa_id)
                ->where('ativa', true)
                ->with(['premios' => fn ($q) => $q->where('ativo', true)])
                ->lockForUpdate()
                ->first();

            if (!$roleta) {
                throw new \DomainException('Roleta indisponível.');
            }

            $credito = $this->creditoDoCliente($roleta, $cliente, lock: true);
            $girosHoje = RoletaGiro::where('roleta_id', $roleta->id)
                ->where('cliente_id', $cliente->id)
                ->whereDate('executado_em', now()->toDateString())
                ->where('tipo_resultado', '!=', 'nova_chance')
                ->count();

            if (!$this->podeGirar($roleta, $cliente, $credito, $girosHoje)) {
                throw new \DomainException('Você não tem giros disponíveis agora.');
            }

            // Antifraude IP: bloqueia se IP já passou do limite hoje
            if ($ip && $roleta->limite_giros_dia_por_ip) {
                $doMesmoIp = RoletaGiro::where('roleta_id', $roleta->id)
                    ->where('ip', $ip)
                    ->whereDate('executado_em', now()->toDateString())
                    ->count();
                if ($doMesmoIp >= $roleta->limite_giros_dia_por_ip) {
                    report(new \RuntimeException("Giro bloqueado por antifraude IP={$ip} roleta={$roleta->id} cliente={$cliente->id}"));
                    throw new \DomainException('Limite diário atingido nesse dispositivo.');
                }
            }

            $premio = $this->sortear($this->premiosElegiveis($roleta, $cliente));
            $resultado = $this->aplicar($roleta, $cliente, $premio, $ip);

            $credito->decrement('giros_disponiveis');

            if ($resultado['tipo_resultado'] === 'nova_chance') {
                $credito->increment('giros_disponiveis');
            }

            // Lista atual de prêmios visíveis (mesma ordem usada no canvas).
            // Mandamos o índice direto: elimina findIndex no cliente e protege
            // contra cache stale (admin editou prêmios durante a sessão).
            $premiosVisiveis = $roleta->premios->values();

            // Caso o resultado seja consolação mas a fatia sorteada não fosse
            // do tipo 'nada' (ex: sorteio_bilhete sem sorteio ativo, ou
            // nenhum prêmio elegível pro cliente), procuramos uma fatia 'nada'
            // visível pra animação parar nela e bater com a mensagem
            // "Quase lá!". Se não houver, mantemos o índice original — pior
            // caso é o admin não ter configurado fatia 'nada' na roleta.
            $premioVisualFinal = $premio;
            if ($resultado['tipo_resultado'] === 'consolacao' && (!$premio || $premio->tipo !== 'nada')) {
                $fatiaNada = $premiosVisiveis->first(fn (RoletaPremio $p) => $p->tipo === 'nada');
                if ($fatiaNada) {
                    $premioVisualFinal = $fatiaNada;
                }
            }

            $premioIndex = $premioVisualFinal
                ? $premiosVisiveis->search(fn (RoletaPremio $p) => $p->id === $premioVisualFinal->id)
                : null;

            return [
                'roleta_id'    => $roleta->id,
                'premio'       => $premio ? [
                    'id'    => $premio->id,
                    'ordem' => $premio->ordem,
                    'label' => $premio->label,
                    'cor'   => $premio->cor,
                    'tipo'  => $premio->tipo,
                ] : null,
                'premio_index' => $premioIndex === false ? null : $premioIndex,
                'premios'      => $premiosVisiveis->map(fn (RoletaPremio $p) => [
                    'id'    => $p->id,
                    'ordem' => $p->ordem,
                    'label' => $p->label,
                    'cor'   => $p->cor,
                    'tipo'  => $p->tipo,
                ])->values(),
                'resultado'    => $resultado,
            ];
        });
    }

    public function creditar(
        Roleta $roleta,
        Cliente $cliente,
        int $giros,
        string $origem = 'manual',
        ?\DateTimeInterface $expiraEm = null,
        ?string $motivoNotificacao = null
    ): RoletaCredito {
        $credito = DB::transaction(function () use ($roleta, $cliente, $giros, $origem, $expiraEm) {
            $credito = RoletaCredito::firstOrNew([
                'roleta_id'  => $roleta->id,
                'cliente_id' => $cliente->id,
            ]);
            $credito->giros_disponiveis = ($credito->giros_disponiveis ?? 0) + $giros;
            $credito->origem = $origem;
            if ($expiraEm) $credito->expira_em = $expiraEm;
            $credito->save();
            return $credito;
        });

        if ($motivoNotificacao !== null) {
            $this->notificarGiroCreditado($cliente, $motivoNotificacao);
        }

        return $credito;
    }

    private function notificarGiroCreditado(Cliente $cliente, string $motivo): void
    {
        if (!$cliente->aceita_whatsapp || !$cliente->telefone) return;

        try {
            $this->whatsapp->enviarEvento(
                $cliente->empresa,
                $cliente->telefone,
                'roleta_giro_creditado',
                [explode(' ', $cliente->nome)[0], $motivo],
                origem: 'roleta'
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function notificarPremioGanho(Cliente $cliente, string $recompensa, string $codigo, ?\DateTimeInterface $expiraEm): void
    {
        if (!$cliente->aceita_whatsapp || !$cliente->telefone) return;

        try {
            $this->whatsapp->enviarEvento(
                $cliente->empresa,
                $cliente->telefone,
                'roleta_premio_ganho',
                [
                    explode(' ', $cliente->nome)[0],
                    $recompensa,
                    $codigo,
                    $expiraEm ? $expiraEm->format('d/m/Y') : 'qualquer momento',
                ],
                origem: 'roleta'
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function creditoDoCliente(Roleta $roleta, Cliente $cliente, bool $lock = false): ?RoletaCredito
    {
        $q = RoletaCredito::where('roleta_id', $roleta->id)
            ->where('cliente_id', $cliente->id);
        if ($lock) $q->lockForUpdate();
        return $q->first();
    }

    private function podeGirar(Roleta $roleta, Cliente $cliente, ?RoletaCredito $credito, int $girosHoje): bool
    {
        if (!$credito || !$credito->valido()) return false;
        if ($girosHoje >= $roleta->limite_giros_dia) return false;
        return true;
    }

    /**
     * Filtra prêmios elegíveis aplicando, na ordem:
     *  - modo campanha: prêmio fora da janela [valido_de, valido_ate] é descartado.
     *  - modo quente: tier_minimo_pontos > cliente.pontos_atual descarta o prêmio.
     *  - modo quantidade: quantidade_max_dia já atingida descarta o prêmio.
     * Quando o filtro elimina todos, retorna [] e o cliente cai na consolação.
     */
    private function premiosElegiveis(Roleta $roleta, Cliente $cliente): array
    {
        $hoje = now()->toDateString();
        $pontosCliente = (float) $cliente->pontos_atual;

        $premios = array_values(array_filter($roleta->premios->all(), function (RoletaPremio $p) use ($hoje, $pontosCliente) {
            if ($p->valido_de && $p->valido_de->toDateString() > $hoje) return false;
            if ($p->valido_ate && $p->valido_ate->toDateString() < $hoje) return false;
            if ($p->tier_minimo_pontos !== null && $pontosCliente < $p->tier_minimo_pontos) return false;
            return true;
        }));

        $idsComLimite = collect($premios)
            ->filter(fn (RoletaPremio $p) => $p->quantidade_max_dia !== null)
            ->pluck('id')
            ->all();

        if (empty($idsComLimite)) {
            return $premios;
        }

        $contagens = RoletaGiro::where('roleta_id', $roleta->id)
            ->whereIn('roleta_premio_id', $idsComLimite)
            ->whereDate('executado_em', $hoje)
            ->groupBy('roleta_premio_id')
            ->selectRaw('roleta_premio_id, COUNT(*) as total')
            ->pluck('total', 'roleta_premio_id');

        return array_values(array_filter($premios, function (RoletaPremio $p) use ($contagens) {
            if ($p->quantidade_max_dia === null) return true;
            return ($contagens[$p->id] ?? 0) < $p->quantidade_max_dia;
        }));
    }

    /**
     * Sorteio ponderado pelo campo `peso`. Retorna null se não houver nenhum
     * prêmio configurado — nesse caso o resultado vira 'consolacao'.
     */
    private function sortear(array $premios): ?RoletaPremio
    {
        $premios = array_values(array_filter($premios, fn (RoletaPremio $p) => $p->peso > 0));
        if (empty($premios)) return null;

        $total = array_sum(array_map(fn (RoletaPremio $p) => $p->peso, $premios));
        $sorteio = random_int(1, $total);
        $acumulado = 0;
        foreach ($premios as $p) {
            $acumulado += $p->peso;
            if ($sorteio <= $acumulado) return $p;
        }
        return $premios[array_key_last($premios)];
    }

    private function aplicar(Roleta $roleta, Cliente $cliente, ?RoletaPremio $premio, ?string $ip): array
    {
        if (!$premio || $premio->tipo === 'nada') {
            return $this->aplicarConsolacao($roleta, $cliente, $premio, $ip);
        }

        // Sorteio_bilhete só funciona se há sorteio ativo na empresa. Sem
        // sorteio ativo, fallback pra consolação (cliente não fica sem nada).
        $sorteioAtivo = null;
        if ($premio->tipo === 'sorteio_bilhete') {
            $sorteioAtivo = Sorteio::where('empresa_id', $cliente->empresa_id)
                ->where('status', 'ativo')
                ->orderByDesc('id')
                ->first();
            if (!$sorteioAtivo) {
                return $this->aplicarConsolacao($roleta, $cliente, $premio, $ip);
            }
        }

        $giro = new RoletaGiro([
            'roleta_id'        => $roleta->id,
            'cliente_id'       => $cliente->id,
            'roleta_premio_id' => $premio->id,
            'tipo_resultado'   => $premio->tipo,
            'ip'               => $ip,
            'executado_em'     => now(),
        ]);

        if ($premio->tipo === 'pontos') {
            $pontos = (int) ($premio->pontos ?? 0);
            if ($pontos > 0) {
                $this->pontuacao->creditar(
                    $cliente,
                    $pontos,
                    'roleta',
                    $giro,
                    "Pontos da roleta: {$premio->label}"
                );
            }
            $giro->pontos_concedidos = $pontos;
        }

        if ($premio->tipo === 'recompensa' && $premio->recompensa_id) {
            $resgate = Resgate::create([
                'empresa_id'    => $cliente->empresa_id,
                'cliente_id'    => $cliente->id,
                'recompensa_id' => $premio->recompensa_id,
                'pontos_usados' => 0,
                'status'        => 'aprovado',
                'observacao'    => "Prêmio da roleta: {$premio->label}",
                'aprovado_em'   => now(),
                'expira_em'     => $roleta->validade_dias ? now()->addDays($roleta->validade_dias) : null,
            ]);
            $giro->recompensa_id = $premio->recompensa_id;
            $giro->resgate_id    = $resgate->id;
            $expiraEm            = $resgate->expira_em;

            $this->notificarPremioGanho($cliente, $premio->label, $resgate->codigo, $resgate->expira_em);
        }

        if ($premio->tipo === 'sorteio_bilhete' && $sorteioAtivo) {
            $giro->save();
            $bilhete = $this->sorteioService->criarBilhete($sorteioAtivo, $cliente, 'roleta', 'roleta_giro:'.$giro->id, $ip);
            return [
                'tipo_resultado'    => 'sorteio_bilhete',
                'pontos_concedidos' => null,
                'recompensa_id'     => null,
                'resgate_id'        => null,
                'expira_em'         => null,
                'sorteio_nome'      => $sorteioAtivo->nome,
                'sorteio_data'      => $sorteioAtivo->data_sorteio->format('d/m/Y'),
                'bilhete_numero'    => $bilhete?->numeroFormatado(),
                'mensagem'          => $this->mensagemBilhete($roleta, $cliente, $sorteioAtivo),
            ];
        }

        $giro->save();

        return [
            'tipo_resultado'    => $premio->tipo,
            'pontos_concedidos' => $giro->pontos_concedidos,
            'recompensa_id'     => $giro->recompensa_id,
            'resgate_id'        => $giro->resgate_id,
            'expira_em'         => isset($expiraEm) ? $expiraEm?->format('d/m/Y') : null,
            'mensagem'          => $this->mensagem($roleta, $premio, $giro, $cliente),
        ];
    }

    private function aplicarConsolacao(Roleta $roleta, Cliente $cliente, ?RoletaPremio $premio, ?string $ip): array
    {
        $pontos = (int) $roleta->pontos_consolacao;

        $giro = new RoletaGiro([
            'roleta_id'        => $roleta->id,
            'cliente_id'       => $cliente->id,
            'roleta_premio_id' => $premio?->id,
            'tipo_resultado'   => 'consolacao',
            'pontos_concedidos'=> $pontos,
            'ip'               => $ip,
            'executado_em'     => now(),
        ]);

        if ($pontos > 0) {
            $this->pontuacao->creditar(
                $cliente,
                $pontos,
                'roleta',
                $giro,
                'Consolação da roleta'
            );
        }

        $giro->save();

        return [
            'tipo_resultado'    => 'consolacao',
            'pontos_concedidos' => $pontos,
            'recompensa_id'     => null,
            'resgate_id'        => null,
            'expira_em'         => null,
            'mensagem'          => strtr($roleta->mensagem_consolacao, [
                '{pontos}'        => (string) $pontos,
                '{primeiro_nome}' => explode(' ', $cliente->nome)[0] ?? '',
                '{nome}'          => $cliente->nome,
            ]),
        ];
    }

    private function mensagem(Roleta $roleta, RoletaPremio $premio, RoletaGiro $giro, Cliente $cliente): string
    {
        $template = match ($premio->tipo) {
            'pontos'      => $roleta->mensagem_pontos      ?: 'Você ganhou {pontos} pontos! 🎉',
            'recompensa'  => $roleta->mensagem_recompensa  ?: 'Você ganhou: {premio}! 🎁',
            'nova_chance' => $roleta->mensagem_nova_chance ?: 'Boa, {primeiro_nome}! Você ganhou um giro extra! 🎰',
            default       => $roleta->mensagem_consolacao,
        };

        return strtr($template, [
            '{pontos}'        => (string) ($giro->pontos_concedidos ?? 0),
            '{premio}'        => $premio->label,
            '{primeiro_nome}' => explode(' ', $cliente->nome)[0] ?? '',
            '{nome}'          => $cliente->nome,
        ]);
    }

    private function mensagemBilhete(Roleta $roleta, Cliente $cliente, Sorteio $sorteio): string
    {
        $primeiroNome = explode(' ', $cliente->nome)[0] ?? '';
        return "🎟️ {$primeiroNome}, você ganhou um bilhete pro sorteio \"{$sorteio->nome}\"! Sorteio dia {$sorteio->data_sorteio->format('d/m/Y')}.";
    }
}
