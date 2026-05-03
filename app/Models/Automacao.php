<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automacao extends Model
{
    protected $table = 'automacoes';

    protected $fillable = [
        'tipo', 'nome', 'mensagem', 'dias_offset', 'valor_referencia',
        'personalizada', 'gatilho',
        'ativo', 'ultima_execucao', 'total_enviados',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'personalizada' => 'boolean',
        'ultima_execucao' => 'datetime',
        'valor_referencia' => 'decimal:2',
    ];

    /**
     * Tipos fixos com gatilho automático embutido (1 registro por tipo).
     * Para criar várias automações, use tipo='personalizada' + gatilho.
     */
    public const TIPOS = [
        'boas_vindas' => 'Boas-vindas (após cadastro)',
        'aniversario' => 'Aniversário do cliente',
        'pontos_vencendo' => 'Pontos próximos do vencimento',
        'inativo_30d' => 'Cliente inativo (30 dias sem compra)',
        'inativo_60d' => 'Cliente inativo (60 dias sem compra)',
        'pos_compra' => 'Após compra (agradecimento)',
        'agradecimento_resgate' => 'Após resgate de prêmio',
        'personalizada' => 'Automação personalizada',
    ];

    /** Gatilhos disponíveis para automações personalizadas. */
    public const GATILHOS = [
        'manual'            => ['rotulo' => 'Manual (só executa quando eu mandar)',         'campo' => null],
        'inativo_dias'      => ['rotulo' => 'Cliente sem comprar há X dias',                'campo' => 'dias_offset'],
        'compras_total'     => ['rotulo' => 'Cliente atingiu N compras totais',             'campo' => 'valor_referencia'],
        'gasto_total'       => ['rotulo' => 'Cliente atingiu R$ X em compras',              'campo' => 'valor_referencia'],
        'cadastro_offset'   => ['rotulo' => 'X dias após o cadastro do cliente',            'campo' => 'dias_offset'],
        'pontos_acumulados' => ['rotulo' => 'Cliente com saldo de X pontos ou mais',        'campo' => 'valor_referencia'],
    ];

    /**
     * Gatilhos que disparam só uma vez por cliente (estado permanente).
     * Os demais (manual, inativo_dias, cadastro_offset) podem repetir
     * conforme a checagem de "já enviado hoje".
     */
    public const GATILHOS_UNICA_VEZ = ['compras_total', 'gasto_total', 'pontos_acumulados', 'cadastro_offset'];

    public const TEMPLATES_PADRAO = [
        'boas_vindas' => "Olá {primeiro_nome}! 🎉\nSeja bem-vindo(a) ao programa de fidelidade da {empresa}!\n\nA cada compra você acumula pontos e pode trocar por prêmios incríveis. Acesse seu app a qualquer momento.",
        'aniversario' => "🎂 Feliz aniversário, {primeiro_nome}!\n\nA {empresa} deseja muita saúde e felicidades. Como presente, te deixamos um bônus de pontos! Confere no app: você tem {pontos} pontos.",
        'pontos_vencendo' => "⏰ Atenção, {primeiro_nome}!\n\nAlguns dos seus pontos da {empresa} estão próximos do vencimento. Você tem {pontos} pontos. Que tal trocar por um prêmio antes que expirem?",
        'inativo_30d' => "Oi {primeiro_nome}, sentimos sua falta! 💛\n\nFaz tempo que não vemos você por aqui na {empresa}. Você ainda tem {pontos} pontos te esperando.",
        'inativo_60d' => "Ei {primeiro_nome}, voltamos a você! 💔\n\nFaz mais de 2 meses sem comprar na {empresa}. Estamos com saudade. Confira nossas novidades e use seus {pontos} pontos!",
        'pos_compra' => "Obrigado pela compra, {primeiro_nome}! ❤️\nVocê acumulou pontos com a gente na {empresa}. Saldo atual: {pontos} pontos. Confira seu app!",
        'agradecimento_resgate' => "Parabéns, {primeiro_nome}! 🎁\n\nSeu resgate na {empresa} foi aprovado. Apresente o código do app na próxima visita. Continue acumulando pontos!",
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(AutomacaoLog::class);
    }
}
