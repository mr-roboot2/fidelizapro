<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sorteio extends Model
{
    use Auditavel;

    protected $fillable = [
        'empresa_id', 'nome', 'descricao', 'imagem',
        'recompensa_id', 'valor_estimado',
        'data_sorteio', 'status',
        'max_bilhetes_por_cliente', 'limite_bilhetes_dia_por_ip',
        'vencedor_cliente_id', 'vencedor_bilhete_id', 'sorteado_em',
    ];

    protected $casts = [
        'data_sorteio'   => 'date',
        'valor_estimado' => 'decimal:2',
        'sorteado_em'    => 'datetime',
    ];

    public const STATUS = [
        'planejado'  => 'Planejado (não aceita bilhetes ainda)',
        'ativo'      => 'Ativo (aceitando bilhetes)',
        'sorteado'   => 'Sorteado (vencedor escolhido)',
        'finalizado' => 'Finalizado (prêmio entregue, arquivado)',
        'cancelado'  => 'Cancelado',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function recompensa(): BelongsTo
    {
        return $this->belongsTo(Recompensa::class);
    }

    public function bilhetes(): HasMany
    {
        return $this->hasMany(SorteioBilhete::class);
    }

    public function vencedor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'vencedor_cliente_id');
    }

    public function vencedorBilhete(): BelongsTo
    {
        return $this->belongsTo(SorteioBilhete::class, 'vencedor_bilhete_id');
    }

    public function aceitaBilhetes(): bool
    {
        return $this->status === 'ativo';
    }
}
