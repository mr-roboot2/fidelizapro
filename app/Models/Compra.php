<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compra extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'cliente_id', 'user_id', 'codigo', 'valor', 'desconto',
        'pontos_gerados', 'cashback_gerado', 'descricao', 'origem', 'meta', 'ip',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'desconto' => 'decimal:2',
        'pontos_gerados' => 'decimal:2',
        'cashback_gerado' => 'decimal:2',
        'meta' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
