<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'empresa_id', 'evento', 'nome_template', 'idioma', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Eventos suportados pelo sistema. Cada um declara os parâmetros que o
     * código envia em ordem — o template na Meta deve ter {{1}}, {{2}}...
     * com esses valores na mesma sequência.
     */
    public const EVENTOS = [
        'otp' => [
            'rotulo' => 'Código de login (OTP)',
            'descricao' => 'Quando o cliente faz login pelo WhatsApp e recebe o código de verificação.',
            'parametros' => ['codigo'],
            'exemplo' => 'Seu código de acesso é {{1}}. Válido por 5 minutos.',
        ],
        'boas_vindas' => [
            'rotulo' => 'Boas-vindas',
            'descricao' => 'Logo após o cadastro do cliente no programa.',
            'parametros' => ['nome_cliente', 'nome_empresa'],
            'exemplo' => 'Olá {{1}}! Bem-vindo(a) ao programa de fidelidade da {{2}}.',
        ],
        'aniversario' => [
            'rotulo' => 'Aniversário do cliente',
            'descricao' => 'No dia do aniversário do cliente (automação diária).',
            'parametros' => ['nome_cliente'],
            'exemplo' => 'Feliz aniversário, {{1}}! Hoje é dia de comemorar com a gente. 🎂',
        ],
        'pontos_vencendo' => [
            'rotulo' => 'Pontos vencendo',
            'descricao' => 'Aviso ao cliente que tem pontos próximos do vencimento.',
            'parametros' => ['nome_cliente', 'pontos'],
            'exemplo' => '{{1}}, você tem {{2}} pontos prestes a vencer. Aproveite!',
        ],
        'inativo_30d' => [
            'rotulo' => 'Cliente inativo (30 dias)',
            'descricao' => 'Cliente sem comprar há 30 dias.',
            'parametros' => ['nome_cliente'],
            'exemplo' => 'Sentimos sua falta, {{1}}! Volte para acumular mais pontos.',
        ],
        'inativo_60d' => [
            'rotulo' => 'Cliente inativo (60 dias)',
            'descricao' => 'Cliente sem comprar há 60 dias.',
            'parametros' => ['nome_cliente'],
            'exemplo' => '{{1}}, faz tempo! Que tal voltar para usar seus pontos?',
        ],
        'cashback_disponivel' => [
            'rotulo' => 'Cashback liberado',
            'descricao' => 'Quando o cashback pendente é liberado.',
            'parametros' => ['nome_cliente', 'valor'],
            'exemplo' => '{{1}}, você tem R$ {{2}} de cashback disponível!',
        ],
        'resgate_aprovado' => [
            'rotulo' => 'Resgate aprovado',
            'descricao' => 'Quando um resgate de prêmio é aprovado.',
            'parametros' => ['nome_cliente', 'recompensa'],
            'exemplo' => '{{1}}, seu resgate de "{{2}}" foi aprovado!',
        ],
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function definicaoEvento(): ?array
    {
        return self::EVENTOS[$this->evento] ?? null;
    }
}
