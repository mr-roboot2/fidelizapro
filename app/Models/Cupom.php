<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Cupom extends Model
{
    protected $table = 'cupons';

    protected $fillable = [
        'beneficio_id', 'cliente_id', 'codigo', 'status',
        'valido_ate', 'usado_em', 'observacao_uso',
    ];

    protected $casts = [
        'valido_ate' => 'datetime',
        'usado_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cupom $c) {
            if (empty($c->codigo)) {
                do {
                    $codigo = strtoupper(Str::random(10));
                } while (self::where('codigo', $codigo)->exists());
                $c->codigo = $codigo;
            }
            if (empty($c->valido_ate)) {
                $c->valido_ate = now()->addDays(30);
            }
        });
    }

    public function beneficio(): BelongsTo
    {
        return $this->belongsTo(Beneficio::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function expirado(): bool
    {
        return $this->valido_ate && $this->valido_ate->isPast();
    }

    public function utilizavel(): bool
    {
        return $this->status === 'disponivel' && !$this->expirado();
    }
}
