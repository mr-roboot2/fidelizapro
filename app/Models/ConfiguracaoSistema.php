<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoSistema extends Model
{
    protected $table = 'configuracoes_sistema';

    protected $fillable = [
        'nome_sistema', 'slogan', 'logo', 'favicon',
        'email_suporte', 'telefone_suporte', 'whatsapp_suporte',
        'razao_social', 'cnpj', 'endereco',
        'cor_primaria', 'cor_secundaria',
        'site_url', 'rodape_html',
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
