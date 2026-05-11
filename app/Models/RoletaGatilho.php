<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoletaGatilho extends Model
{
    use Auditavel;

    protected $table = 'roleta_gatilhos';

    protected $fillable = [
        'roleta_id', 'tipo', 'valor', 'giros', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Cada tipo declara o que o campo `valor` significa (rotulo + sufixo).
     * Tipos sem `valor` deixam `campo` como null.
     */
    public const TIPOS = [
        'primeiro_cadastro'   => ['rotulo' => 'Primeiro cadastro',                                'campo' => null,    'sufixo' => null],
        'aniversario'         => ['rotulo' => 'Aniversário do cliente',                           'campo' => null,    'sufixo' => null],
        'indicacao'           => ['rotulo' => 'Indicação realizada (indicado se cadastrou)',       'campo' => null,    'sufixo' => null],
        'compra_acima'        => ['rotulo' => 'Compra acima de R$ X',                             'campo' => 'valor', 'sufixo' => 'reais'],
        'inativo_dias'        => ['rotulo' => 'Cliente inativo há X dias',                        'campo' => 'valor', 'sufixo' => 'dias'],
        'atingiu_pontos'      => ['rotulo' => 'Cliente atingiu X pontos',                         'campo' => 'valor', 'sufixo' => 'pontos'],
        'vip_gasto'           => ['rotulo' => 'Cliente VIP (gastou R$ X no total)',               'campo' => 'valor', 'sufixo' => 'reais'],
        'recorrente_compras'  => ['rotulo' => 'Cliente recorrente (X compras no total)',          'campo' => 'valor', 'sufixo' => 'compras'],
        'dia_fraco'           => ['rotulo' => 'Dia fraco de movimento (auto-detecta os N dias mais fracos)', 'campo' => 'valor', 'sufixo' => 'dias fracos'],
    ];

    public function roleta(): BelongsTo
    {
        return $this->belongsTo(Roleta::class);
    }
}
