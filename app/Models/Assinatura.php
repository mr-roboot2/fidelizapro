<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assinatura extends Model
{
    use Auditavel;

    protected $fillable = [
        'empresa_id', 'plano_id', 'status', 'gateway',
        'gateway_subscription_id', 'gateway_customer_id',
        'valor_mensal', 'inicio', 'proximo_vencimento',
        'cancelada_em', 'trial_ate', 'meta',
    ];

    protected $casts = [
        'valor_mensal' => 'decimal:2',
        'inicio' => 'date',
        'proximo_vencimento' => 'date',
        'cancelada_em' => 'date',
        'trial_ate' => 'date',
        'meta' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class)->latest('vencimento');
    }

    public function emTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ate && $this->trial_ate->isFuture();
    }
}
