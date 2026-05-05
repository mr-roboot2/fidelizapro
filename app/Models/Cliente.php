<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'empresa_id', 'nome', 'telefone', 'email', 'foto', 'cpf', 'data_nascimento',
        'password', 'codigo_qr', 'codigo_indicacao', 'indicado_por_id',
        'pontos_atual', 'cashback_atual', 'cashback_pendente', 'total_gasto', 'total_compras',
        'ultimo_acesso', 'ultimo_ip', 'ultima_compra', 'ativo', 'aceita_whatsapp',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'ultimo_acesso' => 'datetime',
            'ultima_compra' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'aceita_whatsapp' => 'boolean',
            'pontos_atual' => 'decimal:2',
            'cashback_atual' => 'decimal:2',
            'cashback_pendente' => 'decimal:2',
            'total_gasto' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cliente $cliente) {
            if (empty($cliente->codigo_qr)) {
                $cliente->codigo_qr = 'CLI-'.strtoupper(Str::random(10));
            }
            if (empty($cliente->codigo_indicacao)) {
                $cliente->codigo_indicacao = strtoupper(Str::random(8));
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function resgates(): HasMany
    {
        return $this->hasMany(Resgate::class);
    }

    public function transacoesPontos(): HasMany
    {
        return $this->hasMany(TransacaoPonto::class);
    }

    public function movimentosCashback(): HasMany
    {
        return $this->hasMany(MovimentoCashback::class);
    }

    public function indicacoes(): HasMany
    {
        return $this->hasMany(Indicacao::class, 'cliente_indicador_id');
    }

    public function indicadoPor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'indicado_por_id');
    }

    /**
     * Compara telefone ignorando formatação — funciona com "(11) 98569-0114",
     * "11985690114", "11 98569-0114" ou qualquer combinação.
     */
    public function scopeWhereTelefone($query, string $telefone)
    {
        $digits = preg_replace('/\D/', '', $telefone);
        return $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(telefone, ' ', ''), '(', ''), ')', ''), '-', '') = ?",
            [$digits]
        );
    }

    public function isAniversariante(): bool
    {
        if (!$this->data_nascimento) return false;
        return $this->data_nascimento->format('m-d') === now()->format('m-d');
    }

    public function diasSemComprar(): ?int
    {
        return $this->ultima_compra?->diffInDays(now());
    }
}
