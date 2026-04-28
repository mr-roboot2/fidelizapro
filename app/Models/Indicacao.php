<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'cliente_indicador_id', 'cliente_indicado_id',
        'telefone_indicado', 'nome_indicado', 'status',
        'pontos_concedidos', 'convertida_em',
    ];

    protected $casts = [
        'pontos_concedidos' => 'decimal:2',
        'convertida_em' => 'datetime',
    ];

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_indicador_id');
    }

    public function indicado(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_indicado_id');
    }
}
