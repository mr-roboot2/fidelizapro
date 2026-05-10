<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoletaCredito extends Model
{
    protected $table = 'roleta_creditos';

    protected $fillable = [
        'roleta_id', 'cliente_id',
        'giros_disponiveis', 'origem', 'expira_em',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
    ];

    public const ORIGENS = [
        'manual'            => 'Crédito manual (admin)',
        'primeiro_cadastro' => 'Primeiro cadastro',
        'consolacao'        => 'Nova chance (consolação)',
    ];

    public function roleta(): BelongsTo
    {
        return $this->belongsTo(Roleta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function valido(): bool
    {
        if ($this->giros_disponiveis <= 0) return false;
        if ($this->expira_em && $this->expira_em->isPast()) return false;
        return true;
    }
}
