<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoletaGatilhoDisparo extends Model
{
    protected $table = 'roleta_gatilho_disparos';

    public $timestamps = false;

    protected $fillable = [
        'roleta_id', 'cliente_id', 'tipo', 'referencia', 'giros_creditados', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function roleta(): BelongsTo
    {
        return $this->belongsTo(Roleta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
