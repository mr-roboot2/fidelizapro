<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plano extends Model
{
    use Auditavel;

    protected $fillable = [
        'nome', 'slug', 'descricao', 'preco_mensal',
        'limite_clientes', 'limite_compras_mes', 'limite_recompensas',
        'limite_parceiros', 'limite_users', 'limite_campanhas_mes',
        'whatsapp_ilimitado', 'automacoes_disponivel',
        'parceiros_disponivel', 'white_label_disponivel',
        'modulos',
        'ativo', 'ordem',
    ];

    protected $casts = [
        'preco_mensal' => 'decimal:2',
        'whatsapp_ilimitado' => 'boolean',
        'automacoes_disponivel' => 'boolean',
        'parceiros_disponivel' => 'boolean',
        'white_label_disponivel' => 'boolean',
        'modulos' => 'array',
        'ativo' => 'boolean',
    ];

    /**
     * Catálogo de módulos que um plano pode habilitar. A chave é usada nos
     * checks (Empresa::temModulo('roleta')) e o valor é o rótulo de UI.
     */
    /**
     * Módulos considerados "avançados" — caem no bloqueio_parcial quando a
     * empresa tem cobrança atrasada entre 8-30 dias. Os básicos (clientes,
     * compras, recompensas) continuam disponíveis pra não derrubar a operação
     * essencial.
     */
    public const MODULOS_AVANCADOS = [
        'roleta', 'sorteio', 'whatsapp', 'automacoes', 'parceiros',
        'campanhas', 'antifraude', 'ai_growth',
    ];

    /**
     * `:sistema` é placeholder pro nome configurado em ConfiguracaoSistema
     * (default "FidelizaPro"). Use rotulosModulos() pra obter o array com o
     * placeholder já substituído; use MODULOS_DISPONIVEIS direto só pra
     * validação (array_keys) ou quando o rótulo for irrelevante.
     */
    public const MODULOS_DISPONIVEIS = [
        'roleta'      => 'Roleta da Sorte',
        'sorteio'     => 'Sorteios',
        'whatsapp'    => 'WhatsApp ilimitado',
        'automacoes'  => 'Automações de mensagens',
        'parceiros'   => 'Parceiros e benefícios',
        'campanhas'   => 'Campanhas em massa',
        'metricas'    => 'Métricas e dashboards',
        'indicacoes'  => 'Indicações entre clientes',
        'antifraude'  => 'Painel antifraude',
        'ai_growth'   => 'AI Growth (análise avançada de vendas e clientes)',
        'white_label' => 'White label completo (sem marca :sistema)',
    ];

    /**
     * Mesma estrutura de MODULOS_DISPONIVEIS, porém com `:sistema` já trocado
     * pelo nome configurado. Use sempre que for renderizar pra UI.
     */
    public static function rotulosModulos(): array
    {
        $nome = ConfiguracaoSistema::instancia()->nome_sistema ?? 'FidelizaPro';
        return array_map(fn ($rotulo) => str_replace(':sistema', $nome, $rotulo), self::MODULOS_DISPONIVEIS);
    }

    public function temModulo(string $chave): bool
    {
        return in_array($chave, $this->modulos ?? [], true);
    }

    protected static function booted(): void
    {
        static::creating(function (Plano $plano) {
            if (empty($plano->slug)) {
                $plano->slug = Str::slug($plano->nome);
            }
        });
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }
}
