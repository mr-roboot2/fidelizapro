<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recompensa extends Model
{
    use HasFactory, Auditavel;

    protected $fillable = [
        'empresa_id', 'nome', 'descricao', 'imagem', 'custo_pontos',
        'estoque', 'estoque_inicial', 'tipo', 'valor_estimado',
        'destaque', 'ativo', 'valido_ate',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
        'destaque' => 'boolean',
        'ativo' => 'boolean',
        'valido_ate' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resgates(): HasMany
    {
        return $this->hasMany(Resgate::class);
    }

    public function disponivel(): bool
    {
        if (!$this->ativo) return false;
        if ($this->valido_ate && $this->valido_ate->isPast()) return false;
        if ($this->estoque !== null && $this->estoque <= 0) return false;
        return true;
    }
}
