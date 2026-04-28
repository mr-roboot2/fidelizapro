<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobranca extends Model
{
    use Auditavel;

    protected $table = 'cobrancas';

    protected $fillable = [
        'assinatura_id', 'empresa_id', 'valor', 'vencimento', 'pago_em',
        'status', 'gateway_charge_id', 'link_pagamento', 'forma_pagamento', 'meta',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'vencimento' => 'date',
        'pago_em' => 'datetime',
        'meta' => 'array',
    ];

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vencida(): bool
    {
        return $this->status === 'pendente' && $this->vencimento->isPast();
    }
}
