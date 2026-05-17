<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficio extends Model
{
    protected $table = 'beneficios';

    protected $fillable = [
        'parceiro_id', 'nome', 'descricao', 'tipo', 'valor', 'condicoes',
        'valido_ate', 'limite_por_cliente', 'limite_total', 'total_resgatados',
        'destaque', 'ativo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valido_ate' => 'date',
        'destaque' => 'boolean',
        'ativo' => 'boolean',
    ];

    public const TIPOS = [
        'desconto_percentual' => 'Desconto %',
        'desconto_valor' => 'Desconto R$',
        'brinde' => 'Brinde',
        'servico_gratis' => 'Serviço grátis',
        'cortesia' => 'Cortesia',
    ];

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function cupons(): HasMany
    {
        return $this->hasMany(Cupom::class);
    }

    public function disponivel(): bool
    {
        if (!$this->ativo) return false;
        // "Válido até 16/05" significa "até o fim do dia 16/05" — sem
        // endOfDay() o benefício expirava em 16/05 00:00:01 e o cliente
        // perdia o dia inteiro de validade.
        if ($this->valido_ate && $this->valido_ate->copy()->endOfDay()->isPast()) return false;
        if ($this->limite_total !== null && $this->total_resgatados >= $this->limite_total) return false;
        return true;
    }

    public function quantidadeJaResgatadaPor(Cliente $cliente): int
    {
        return Cupom::where('beneficio_id', $this->id)
            ->where('cliente_id', $cliente->id)
            ->whereIn('status', ['disponivel', 'usado'])
            ->count();
    }

    public function podeResgatarPor(Cliente $cliente): bool
    {
        if (!$this->disponivel()) return false;
        if ($this->limite_por_cliente !== null
            && $this->quantidadeJaResgatadaPor($cliente) >= $this->limite_por_cliente) {
            return false;
        }
        return true;
    }

    public function descricaoTipo(): string
    {
        return match ($this->tipo) {
            'desconto_percentual' => number_format((float) $this->valor, 0).'% de desconto',
            'desconto_valor' => 'R$ '.number_format((float) $this->valor, 2, ',', '.').' de desconto',
            'brinde' => 'Brinde',
            'servico_gratis' => 'Serviço grátis',
            'cortesia' => 'Cortesia',
            default => $this->tipo,
        };
    }
}
