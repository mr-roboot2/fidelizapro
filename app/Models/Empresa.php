<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Empresa extends Model
{
    use HasFactory, Auditavel;

    protected $table = 'empresas';

    protected $fillable = [
        'nome', 'slug', 'cnpj', 'telefone', 'email', 'endereco', 'logo',
        'cor_primaria', 'cor_secundaria', 'pontos_por_real',
        'cashback_percentual', 'dias_liberar_cashback', 'validade_pontos_dias', 'ativo',
        'pdv_secret', 'plano_id',
        'whatsapp_provider', 'whatsapp_api_url', 'whatsapp_api_token',
        'whatsapp_instance', 'whatsapp_phone_id', 'whatsapp_ativo',
        'whatsapp_webhook_verify_token', 'whatsapp_waba_id',
        'rate_limit_auth', 'rate_limit_pdv',
        'otp_max_por_telefone', 'otp_max_tentativas', 'max_resgates_24h',
    ];

    protected $casts = [
        'pontos_por_real' => 'decimal:2',
        'cashback_percentual' => 'decimal:2',
        'ativo' => 'boolean',
        'whatsapp_ativo' => 'boolean',
        'rate_limit_auth' => 'integer',
        'rate_limit_pdv' => 'integer',
        'otp_max_por_telefone' => 'integer',
        'otp_max_tentativas' => 'integer',
        'max_resgates_24h' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Empresa $empresa) {
            if (empty($empresa->slug)) {
                $empresa->slug = Str::slug($empresa->nome);
            }
            if (empty($empresa->pdv_secret)) {
                $empresa->pdv_secret = 'sk_'.Str::random(40);
            }
        });
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class);
    }

    public function assinatura()
    {
        return $this->hasOne(Assinatura::class)->whereNotIn('status', ['cancelada']);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function recompensas(): HasMany
    {
        return $this->hasMany(Recompensa::class);
    }

    public function resgates(): HasMany
    {
        return $this->hasMany(Resgate::class);
    }

    public function regrasPontuacao(): HasMany
    {
        return $this->hasMany(RegraPontuacao::class);
    }

    public function campanhas(): HasMany
    {
        return $this->hasMany(Campanha::class);
    }
}
