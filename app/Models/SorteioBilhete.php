<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SorteioBilhete extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sorteio_id', 'cliente_id', 'numero', 'origem', 'referencia', 'ip', 'created_at',
    ];

    /**
     * Formato visual do número (zero-padded, 4 dígitos). #0042
     */
    public function numeroFormatado(): string
    {
        return '#'.str_pad((string) $this->numero, 4, '0', STR_PAD_LEFT);
    }

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const ORIGENS = [
        'roleta'     => 'Roleta da Sorte',
        'compra'     => 'Compra',
        'manual'     => 'Crédito manual (admin)',
        'consolacao' => 'Consolação',
    ];

    public function sorteio(): BelongsTo
    {
        return $this->belongsTo(Sorteio::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
