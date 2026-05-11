<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoSistema extends Model
{
    protected $table = 'configuracoes_sistema';

    protected $fillable = [
        'nome_sistema', 'slogan', 'logo', 'logo_bg_color', 'logo_scale',
        'favicon', 'favicon_bg_color', 'favicon_scale',
        'email_suporte', 'telefone_suporte', 'whatsapp_suporte',
        'razao_social', 'cnpj', 'endereco',
        'cor_primaria', 'cor_secundaria',
        'site_url', 'rodape_html',
        'whatsapp_provider', 'whatsapp_api_url', 'whatsapp_api_token',
        'whatsapp_client_token',
        'whatsapp_instance', 'whatsapp_phone_id', 'whatsapp_waba_id',
        'whatsapp_webhook_verify_token', 'whatsapp_ativo',
        'horario_automacoes', 'horario_cashback',
        'rate_limit_auth', 'rate_limit_pdv',
        'otp_max_por_telefone', 'otp_max_tentativas', 'max_resgates_24h',
    ];

    protected $casts = [
        'whatsapp_ativo' => 'boolean',
        'rate_limit_auth' => 'integer',
        'rate_limit_pdv' => 'integer',
        'otp_max_por_telefone' => 'integer',
        'otp_max_tentativas' => 'integer',
        'max_resgates_24h' => 'integer',
    ];

    /**
     * Singleton: sempre retorna a mesma row (cria se não existir).
     * Cacheado dentro da request pra evitar múltiplas queries.
     */
    protected static ?self $cached = null;

    public static function instancia(): self
    {
        if (static::$cached) return static::$cached;

        static::$cached = static::firstOrCreate(['id' => 1], [
            'nome_sistema'   => 'FidelizaPro',
            'cor_primaria'   => '#6366f1',
            'cor_secundaria' => '#8b5cf6',
        ]);

        return static::$cached;
    }

    public static function limparCache(): void
    {
        static::$cached = null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon ? asset('storage/'.$this->favicon) : null;
    }
}
