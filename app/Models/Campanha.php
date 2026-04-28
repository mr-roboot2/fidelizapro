<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campanha extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'nome', 'mensagem', 'canal', 'segmento', 'filtros',
        'status', 'agendada_para', 'enviada_em',
        'total_destinatarios', 'total_enviados', 'total_falhas',
    ];

    protected $casts = [
        'filtros' => 'array',
        'agendada_para' => 'datetime',
        'enviada_em' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function envios(): HasMany
    {
        return $this->hasMany(CampanhaEnvio::class);
    }
}
