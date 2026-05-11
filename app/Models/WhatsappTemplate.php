<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'evento', 'nome_template', 'idioma', 'texto', 'ativo',
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
        'resgate_solicitado' => [
            'rotulo' => 'Resgate solicitado',
            'descricao' => 'Confirmação imediata quando o cliente solicita um resgate pelo app (status pendente, aguardando aprovação da loja).',
            'parametros' => ['nome_cliente', 'recompensa', 'codigo', 'pontos_usados'],
            'exemplo' => '🎁 {{1}}, recebemos seu pedido de resgate de "{{2}}"! Código: {{3}} (−{{4}} pts). Aguarde a confirmação da loja.',
        ],
        'resgate_aprovado' => [
            'rotulo' => 'Resgate aprovado',
            'descricao' => 'Quando um resgate de prêmio é aprovado.',
            'parametros' => ['nome_cliente', 'recompensa'],
            'exemplo' => '{{1}}, seu resgate de "{{2}}" foi aprovado!',
        ],
        'roleta_giro_creditado' => [
            'rotulo' => 'Giro de roleta creditado',
            'descricao' => 'Quando o cliente ganha um giro da Roleta da Sorte (cadastro, aniversário, compra, indicação, etc).',
            'parametros' => ['nome_cliente', 'motivo'],
            'exemplo' => '🎰 {{1}}, você ganhou um giro na Roleta da Sorte! Motivo: {{2}}. Abra o app e gire pra ganhar prêmios!',
        ],
        'roleta_premio_ganho' => [
            'rotulo' => 'Prêmio ganho na roleta',
            'descricao' => 'Quando o cliente acerta um prêmio (recompensa) girando a Roleta da Sorte.',
            'parametros' => ['nome_cliente', 'recompensa', 'codigo', 'expira_em'],
            'exemplo' => '🎉 {{1}}, você ganhou {{2}} na Roleta da Sorte! Apresente o código {{3}} até {{4}}.',
        ],
        'sorteio_bilhete_ganho' => [
            'rotulo' => 'Bilhete de sorteio ganho',
            'descricao' => 'Quando o cliente recebe um bilhete pra um sorteio (geralmente via roleta).',
            'parametros' => ['nome_cliente', 'nome_sorteio', 'data_sorteio'],
            'exemplo' => '🎟️ {{1}}, você ganhou um bilhete pro sorteio "{{2}}"! Sorteio dia {{3}}.',
        ],
        'sorteio_vencedor' => [
            'rotulo' => 'Vencedor de sorteio',
            'descricao' => 'Quando o cliente é o vencedor sorteado de uma rifa.',
            'parametros' => ['nome_cliente', 'nome_sorteio'],
            'exemplo' => '🏆 {{1}}, parabéns! Você foi sorteado(a) em "{{2}}"! Entre em contato com a loja pra retirar.',
        ],
        'cobranca_vence_em_breve' => [
            'rotulo' => 'Cobrança vence em breve (empresa)',
            'descricao' => 'Aviso pra empresa-cliente quando a cobrança da assinatura está próxima de vencer.',
            'parametros' => ['nome_empresa', 'valor', 'dias_pro_vencimento'],
            'exemplo' => 'Olá {{1}}, sua cobrança de R$ {{2}} vence em {{3}} dia(s). Pague pra não perder acesso ao sistema.',
        ],
        'cobranca_vencida' => [
            'rotulo' => 'Cobrança vencida (empresa)',
            'descricao' => 'Aviso pra empresa-cliente quando a cobrança da assinatura passou do vencimento.',
            'parametros' => ['nome_empresa', 'valor', 'dias_atraso'],
            'exemplo' => '⚠️ {{1}}, sua cobrança de R$ {{2}} venceu há {{3}} dia(s). Regularize pra evitar bloqueio.',
        ],
    ];

    public function definicaoEvento(): ?array
    {
        return self::EVENTOS[$this->evento] ?? null;
    }
}
