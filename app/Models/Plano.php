<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plano extends Model
{
    use Auditavel;

    protected $fillable = [
        'nome', 'slug', 'descricao', 'preco_mensal',
        'limite_clientes', 'limite_compras_mes', 'limite_recompensas',
        'limite_parceiros', 'limite_users', 'limite_campanhas_mes',
        'whatsapp_ilimitado', 'automacoes_disponivel',
        'parceiros_disponivel', 'white_label_disponivel',
        'ativo', 'ordem',
    ];

    protected $casts = [
        'preco_mensal' => 'decimal:2',
        'whatsapp_ilimitado' => 'boolean',
        'automacoes_disponivel' => 'boolean',
        'parceiros_disponivel' => 'boolean',
        'white_label_disponivel' => 'boolean',
        'ativo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Plano $plano) {
            if (empty($plano->slug)) {
                $plano->slug = Str::slug($plano->nome);
            }
        });
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }
}
