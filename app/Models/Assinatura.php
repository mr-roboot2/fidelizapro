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
        'empresa_id', 'plano_id', 'plano_id_pendente', 'status', 'gateway',
        'gateway_subscription_id', 'gateway_customer_id',
        'valor_mensal', 'inicio', 'proximo_vencimento',
        'cancelada_em', 'trial_ate', 'meta',
    ];

    protected $casts = [
        'valor_mensal' => 'decimal:2',
        'inicio' => 'date',
        'proximo_vencimento' => 'date',
        'cancelada_em' => 'date',
        // Datetime (não date): trial_ate é setado com addDays(7)->setTime()
        // ou now()->addDays(N) em AssinaturaService/EmpresaObserver, ambos
        // com hora exata. Cast 'date' truncava pra 00:00:00 — empresa criada
        // 09/05 14h pra 7 dias trial ficava expirada em 16/05 às 14h em vez
        // do esperado 23:59:59 de 16/05 (perdia até 24h de trial).
        'trial_ate' => 'datetime',
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

    public function planoPendente(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_id_pendente');
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
