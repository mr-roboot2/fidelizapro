<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronExecucao extends Model
{
    protected $table = 'cron_execucoes';

    protected $fillable = [
        'comando', 'iniciado_em', 'terminado_em', 'duracao_ms',
        'status', 'exit_code', 'output', 'erro', 'origem',
    ];

    protected $casts = [
        'iniciado_em'  => 'datetime',
        'terminado_em' => 'datetime',
    ];

    public const COMANDOS_MONITORADOS = [
        'cashback:liberar'             => 'Liberação de cashback pendente',
        'automacoes:executar'          => 'Envio de automações WhatsApp',
        'roleta:processar-gatilhos'    => 'Gatilhos da Roleta da Sorte',
        'assinaturas:processar'        => 'Cobranças recorrentes + PIX expirado + notificações',
    ];
}
