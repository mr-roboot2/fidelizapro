<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoletaGiro extends Model
{
    protected $table = 'roleta_giros';

    protected $fillable = [
        'roleta_id', 'cliente_id', 'roleta_premio_id',
        'tipo_resultado', 'pontos_concedidos',
        'recompensa_id', 'resgate_id', 'ip', 'executado_em',
    ];

    protected $casts = [
        'executado_em' => 'datetime',
    ];

    public function roleta(): BelongsTo
    {
        return $this->belongsTo(Roleta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function premio(): BelongsTo
    {
        return $this->belongsTo(RoletaPremio::class, 'roleta_premio_id');
    }

    public function recompensa(): BelongsTo
    {
        return $this->belongsTo(Recompensa::class);
    }

    public function resgate(): BelongsTo
    {
        return $this->belongsTo(Resgate::class);
    }
}
