<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Parceiro extends Model
{
    use Auditavel;

    protected $table = 'parceiros';

    protected $fillable = [
        'empresa_id', 'nome', 'slug', 'descricao', 'categoria', 'logo',
        'telefone', 'email', 'endereco', 'site',
        'validacao_secret', 'ativo',
    ];

    protected $hidden = ['validacao_secret'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Parceiro $p) {
            if (empty($p->slug)) {
                $p->slug = Str::slug($p->nome).'-'.Str::random(4);
            }
            if (empty($p->validacao_secret)) {
                $p->validacao_secret = Str::random(32);
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function beneficios(): HasMany
    {
        return $this->hasMany(Beneficio::class);
    }

    public function urlValidacao(): string
    {
        return url("/parceiro/{$this->validacao_secret}");
    }
}
