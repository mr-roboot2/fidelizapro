<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoletaPremio extends Model
{
    use Auditavel;

    protected $table = 'roleta_premios';

    protected $fillable = [
        'roleta_id', 'ordem', 'label', 'cor',
        'tipo', 'recompensa_id', 'pontos',
        'peso', 'quantidade_max_dia',
        'tier_minimo_pontos', 'valido_de', 'valido_ate',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'valido_de' => 'date',
        'valido_ate' => 'date',
    ];

    public const TIPOS = [
        'recompensa'  => 'Prêmio do catálogo',
        'pontos'      => 'Pontos extras',
        'nova_chance' => 'Nova chance (gira de novo)',
        'nada'        => 'Nada (cai na consolação)',
    ];

    public function roleta(): BelongsTo
    {
        return $this->belongsTo(Roleta::class);
    }

    public function recompensa(): BelongsTo
    {
        return $this->belongsTo(Recompensa::class);
    }
}
