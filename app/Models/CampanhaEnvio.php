<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampanhaEnvio extends Model
{
    use HasFactory;

    protected $fillable = [
        'campanha_id', 'cliente_id', 'status', 'enviado_em', 'erro',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
    ];

    public function campanha(): BelongsTo
    {
        return $this->belongsTo(Campanha::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
