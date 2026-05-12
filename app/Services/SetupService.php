<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Empresa;
use App\Models\Parceiro;
use App\Models\Recompensa;
use App\Models\RegraPontuacao;
use App\Models\Roleta;
use App\Models\Sorteio;

class SetupService
{
    /**
     * Retorna a lista de passos do setup adaptada aos módulos do plano
     * da empresa. Cada passo:
     *   chave, icone, titulo, descricao, rota_acao, label_acao, obrigatorio,
     *   concluido (bool, auto-detectado)
     */
    public function passos(Empresa $empresa): array
    {
        $passos = [];

        // ─── BÁSICOS (todas empresas) ────────────────────────────────────────
        $passos[] = [
            'chave' => 'identidade',
            'icone' => 'ri-palette-line',
            'titulo' => 'Identidade visual',
            'descricao' => 'Logo, cor primária e nome da loja.',
            'rota_acao' => 'admin.configuracoes.edit',
            'label_acao' => 'Configurar',
            'obrigatorio' => true,
            'concluido' => !empty($empresa->logo) && !empty($empresa->cor_primaria),
        ];

        $passos[] = [
            'chave' => 'fidelidade',
            'icone' => 'ri-coin-line',
            'titulo' => 'Programa de fidelidade',
            'descricao' => 'Defina pontos por R$, % cashback ou ambos.',
            'rota_acao' => 'admin.configuracoes.edit',
            'label_acao' => 'Configurar',
            'obrigatorio' => true,
            'concluido' => $empresa->pontos_por_real > 0 || $empresa->cashback_percentual > 0,
        ];

        $passos[] = [
            'chave' => 'regras',
            'icone' => 'ri-stack-line',
            'titulo' => 'Regra de pontuação',
            'descricao' => 'Pelo menos uma regra ativa pra calcular pontos.',
            'rota_acao' => 'admin.regras.index',
            'label_acao' => 'Ver regras',
            'obrigatorio' => true,
            'concluido' => RegraPontuacao::where('empresa_id', $empresa->id)->where('ativo', true)->exists(),
        ];

        $passos[] = [
            'chave' => 'recompensa',
            'icone' => 'ri-gift-line',
            'titulo' => 'Cadastrar uma recompensa',
            'descricao' => 'Pelo menos um prêmio no catálogo pra clientes trocarem pontos.',
            'rota_acao' => 'admin.recompensas.create',
            'label_acao' => 'Cadastrar',
            'obrigatorio' => true,
            'concluido' => Recompensa::where('empresa_id', $empresa->id)->where('ativo', true)->exists(),
        ];

        $passos[] = [
            'chave' => 'pwa',
            'icone' => 'ri-smartphone-line',
            'titulo' => 'Compartilhar PWA com os clientes',
            'descricao' => 'Veja o link e o QR code do app pra colar na loja física.',
            'rota_acao' => 'admin.pwa.share',
            'label_acao' => 'Ver QR Code',
            'obrigatorio' => false,
            'concluido' => false, // marca via session quando o cliente visualiza
        ];

        // ─── POR MÓDULO ──────────────────────────────────────────────────────
        if ($empresa->temModulo('roleta')) {
            $passos[] = [
                'chave' => 'roleta',
                'icone' => 'ri-bubble-chart-line',
                'titulo' => 'Ativar Roleta da Sorte',
                'descricao' => 'Configure a roleta com pelo menos um prêmio ativo.',
                'rota_acao' => 'admin.roleta.index',
                'label_acao' => 'Configurar',
                'obrigatorio' => false,
                'concluido' => Roleta::where('empresa_id', $empresa->id)
                    ->where('ativa', true)
                    ->whereHas('premios', fn ($q) => $q->where('ativo', true))
                    ->exists(),
            ];
        }

        if ($empresa->temModulo('sorteio')) {
            $passos[] = [
                'chave' => 'sorteio',
                'icone' => 'ri-ticket-2-line',
                'titulo' => 'Criar primeiro sorteio',
                'descricao' => 'Engaje os clientes com sorteios de prêmios.',
                'rota_acao' => 'admin.sorteios.create',
                'label_acao' => 'Criar sorteio',
                'obrigatorio' => false,
                'concluido' => Sorteio::where('empresa_id', $empresa->id)->exists(),
            ];
        }

        if ($empresa->temModulo('parceiros')) {
            $passos[] = [
                'chave' => 'parceiros',
                'icone' => 'ri-shake-hands-line',
                'titulo' => 'Cadastrar parceiro',
                'descricao' => 'Outras lojas onde seus clientes podem usar benefícios.',
                'rota_acao' => 'admin.parceiros.create',
                'label_acao' => 'Cadastrar',
                'obrigatorio' => false,
                'concluido' => Parceiro::where('empresa_id', $empresa->id)->exists(),
            ];
        }

        if ($empresa->temModulo('campanhas')) {
            $passos[] = [
                'chave' => 'campanhas',
                'icone' => 'ri-megaphone-line',
                'titulo' => 'Primeira campanha',
                'descricao' => 'Envie WhatsApp em massa pra segmentos de clientes.',
                'rota_acao' => 'super.campanhas.index', // campanhas são geridas no super
                'label_acao' => 'Ver campanhas',
                'obrigatorio' => false,
                'concluido' => Campanha::where('empresa_id', $empresa->id)->exists(),
            ];
        }

        if ($empresa->temModulo('ai_growth')) {
            $passos[] = [
                'chave' => 'ai_growth',
                'icone' => 'ri-magic-line',
                'titulo' => 'Conhecer o AI Growth',
                'descricao' => 'Analise vendas, clientes e exporte relatórios em PDF/CSV.',
                'rota_acao' => 'admin.ai-growth.index',
                'label_acao' => 'Abrir',
                'obrigatorio' => false,
                'concluido' => false, // marcado por session quando visita
            ];
        }

        return $passos;
    }

    public function resumo(Empresa $empresa): array
    {
        $passos = $this->passos($empresa);
        $obrigatorios = collect($passos)->where('obrigatorio', true);
        $concluidosObrig = $obrigatorios->where('concluido', true)->count();
        $totalObrig = $obrigatorios->count();
        $concluidosTotal = collect($passos)->where('concluido', true)->count();
        $total = count($passos);

        return [
            'passos'             => $passos,
            'total'              => $total,
            'concluidos'         => $concluidosTotal,
            'percentual'         => $total > 0 ? round($concluidosTotal / $total * 100) : 0,
            'obrigatorios_ok'    => $concluidosObrig === $totalObrig,
            'obrigatorios_total' => $totalObrig,
            'obrigatorios_concluidos' => $concluidosObrig,
        ];
    }
}
