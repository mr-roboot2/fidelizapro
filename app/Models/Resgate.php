<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Resgate extends Model
{
    use HasFactory, Auditavel;

    protected $fillable = [
        'empresa_id', 'cliente_id', 'recompensa_id', 'codigo', 'pontos_usados',
        'status', 'observacao', 'aprovado_por', 'aprovado_em', 'expira_em',
        'entregue_em', 'cancelado_em', 'ip',
    ];

    protected $casts = [
        'aprovado_em' => 'datetime',
        'expira_em'   => 'datetime',
        'entregue_em' => 'datetime',
        'cancelado_em' => 'datetime',
    ];

    public function expirado(): bool
    {
        if (!$this->expira_em) return false;
        if (in_array($this->status, ['entregue', 'cancelado'])) return false;
        return $this->expira_em->isPast();
    }

    protected static function booted(): void
    {
        static::creating(function (Resgate $resgate) {
            if (empty($resgate->codigo)) {
                $resgate->codigo = 'RSG-'.strtoupper(Str::random(8));
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function recompensa(): BelongsTo
    {
        return $this->belongsTo(Recompensa::class);
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }
}
