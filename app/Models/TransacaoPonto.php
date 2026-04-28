<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TransacaoPonto extends Model
{
    use HasFactory;

    protected $table = 'transacoes_pontos';

    protected $fillable = [
        'empresa_id', 'cliente_id', 'tipo', 'origem', 'pontos',
        'saldo_anterior', 'saldo_posterior', 'referencia_type',
        'referencia_id', 'descricao', 'expira_em',
    ];

    protected $casts = [
        'pontos' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_posterior' => 'decimal:2',
        'expira_em' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }
}
