<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomacaoLog extends Model
{
    protected $table = 'automacao_logs';

    protected $fillable = [
        'automacao_id', 'cliente_id', 'sucesso', 'mensagem_enviada', 'erro',
    ];

    protected $casts = [
        'sucesso' => 'boolean',
    ];

    public function automacao(): BelongsTo
    {
        return $this->belongsTo(Automacao::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
