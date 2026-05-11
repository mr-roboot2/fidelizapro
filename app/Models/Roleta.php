<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roleta extends Model
{
    use Auditavel;

    protected $fillable = [
        'empresa_id', 'nome', 'ativa', 'modo',
        'tempo_min_ms', 'tempo_max_ms',
        'mensagem_consolacao', 'pontos_consolacao',
        'limite_giros_dia', 'validade_dias',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public const MODOS = [
        'porcentagem' => 'Por porcentagem (peso de cada prêmio)',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function premios(): HasMany
    {
        return $this->hasMany(RoletaPremio::class)->orderBy('ordem');
    }

    public function creditos(): HasMany
    {
        return $this->hasMany(RoletaCredito::class);
    }

    public function giros(): HasMany
    {
        return $this->hasMany(RoletaGiro::class);
    }

    public function gatilhos(): HasMany
    {
        return $this->hasMany(RoletaGatilho::class);
    }
}
