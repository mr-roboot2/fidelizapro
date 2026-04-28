<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegraPontuacao extends Model
{
    use HasFactory, Auditavel;

    protected $table = 'regras_pontuacao';

    protected $fillable = [
        'empresa_id', 'nome', 'tipo', 'valor_minimo', 'valor_maximo',
        'pontos_por_real', 'multiplicador', 'pontos_fixos',
        'data_inicio', 'data_fim', 'ativo',
    ];

    protected $casts = [
        'valor_minimo' => 'decimal:2',
        'valor_maximo' => 'decimal:2',
        'pontos_por_real' => 'decimal:2',
        'multiplicador' => 'decimal:2',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vigente(): bool
    {
        if (!$this->ativo) return false;
        $hoje = now()->toDateString();
        if ($this->data_inicio && $this->data_inicio->toDateString() > $hoje) return false;
        if ($this->data_fim && $this->data_fim->toDateString() < $hoje) return false;
        return true;
    }
}
