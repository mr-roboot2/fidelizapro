<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCodigo extends Model
{
    protected $table = 'otp_codigos';

    protected $fillable = [
        'empresa_id', 'telefone', 'codigo', 'expires_at', 'usado', 'ip', 'tentativas',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'usado' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function expirado(): bool
    {
        return $this->expires_at->isPast();
    }
}
