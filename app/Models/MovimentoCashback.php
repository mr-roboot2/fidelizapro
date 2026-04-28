<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimentoCashback extends Model
{
    use HasFactory;

    protected $table = 'movimentos_cashback';

    protected $fillable = [
        'empresa_id', 'cliente_id', 'tipo', 'origem', 'valor',
        'saldo_anterior', 'saldo_posterior', 'referencia_type',
        'referencia_id', 'descricao', 'liberado_em', 'processado',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_posterior' => 'decimal:2',
        'liberado_em' => 'datetime',
        'processado' => 'boolean',
    ];

    public function pendente(): bool
    {
        return !$this->processado && $this->liberado_em && $this->liberado_em->isFuture();
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }
}
