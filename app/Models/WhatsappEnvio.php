<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappEnvio extends Model
{
    protected $table = 'whatsapp_envios';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id', 'cliente_id', 'telefone', 'evento', 'origem',
        'mensagem', 'provider', 'sucesso', 'erro', 'external_id',
    ];

    protected $casts = [
        'sucesso' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
