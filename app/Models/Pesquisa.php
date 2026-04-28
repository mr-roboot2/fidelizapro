<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesquisa extends Model
{
    use HasFactory;

    protected $table = 'pesquisas_satisfacao';

    protected $fillable = [
        'empresa_id', 'cliente_id', 'compra_id', 'nota', 'comentario', 'respostas',
    ];

    protected $casts = [
        'respostas' => 'array',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function isPromotor(): bool
    {
        return $this->nota >= 4;
    }

    public function isDetrator(): bool
    {
        return $this->nota <= 2;
    }
}
