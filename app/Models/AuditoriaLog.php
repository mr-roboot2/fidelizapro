<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'empresa_id', 'acao', 'entidade', 'entidade_id',
        'antes', 'depois', 'descricao', 'ip', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'antes' => 'array',
        'depois' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function entidadeNomeCurto(): string
    {
        if (!$this->entidade) return '';
        return class_basename($this->entidade);
    }
}
