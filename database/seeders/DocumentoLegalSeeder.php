<?php

namespace Database\Seeders;

use App\Models\DocumentoLegal;
use Illuminate\Database\Seeder;

class DocumentoLegalSeeder extends Seeder
{
    public function run(): void
    {
        DocumentoLegal::updateOrCreate(['slug' => 'politica-privacidade'], [
            'titulo' => 'Política de Privacidade',
            'conteudo' => $this->politicaPadrao(),
        ]);

        DocumentoLegal::updateOrCreate(['slug' => 'termos-de-uso'], [
            'titulo' => 'Termos de Uso',
            'conteudo' => $this->termosPadrao(),
        ]);
    }

    protected function politicaPadrao(): string
    {
        return <<<HTML
<h2>1. Informações que coletamos</h2>
<p>Para participar do programa de fidelidade, coletamos:</p>
<ul>
    <li>Nome completo</li>
    <li>Telefone (usado para login e identificação no caixa)</li>
    <li>E-mail (opcional)</li>
    <li>Data de nascimento (opcional, para benefícios de aniversário)</li>
    <li>CPF (opcional)</li>
    <li>Histórico de compras realizadas no estabelecimento</li>
</ul>

<h2>2. Como usamos seus dados</h2>
<p>Seus dados são usados exclusivamente para:</p>
<ul>
    <li>Identificá-lo no programa de fidelidade</li>
    <li>Acumular pontos e cashback nas suas compras</li>
    <li>Enviar comunicações sobre o programa (promoções, prêmios, aniversário) — apenas se você autorizar</li>
    <li>Validar resgates e cupons no caixa</li>
</ul>

<h2>3. Compartilhamento de dados</h2>
<p>Não vendemos nem cedemos seus dados a terceiros. As empresas que oferecem este programa têm acesso apenas aos dados dos próprios clientes.</p>

<h2>4. Seus direitos (LGPD)</h2>
<p>Você pode a qualquer momento:</p>
<ul>
    <li>Acessar e corrigir seus dados pelo aplicativo</li>
    <li>Solicitar a exclusão da sua conta entrando em contato com a empresa</li>
    <li>Cancelar o recebimento de comunicações</li>
</ul>

<h2>5. Segurança</h2>
<p>Suas informações são armazenadas em servidores seguros e a senha é criptografada. Apresente seu QR Code apenas no caixa do estabelecimento.</p>

<h2>6. Contato</h2>
<p>Para qualquer dúvida sobre esta política, entre em contato com a empresa onde você é cliente.</p>

<p><em>Última atualização: {DATA}</em></p>
HTML;
    }

    protected function termosPadrao(): string
    {
        return <<<HTML
<h2>1. Aceitação dos termos</h2>
<p>Ao se cadastrar e usar este programa de fidelidade, você concorda com os termos descritos abaixo.</p>

<h2>2. Acúmulo de pontos e cashback</h2>
<ul>
    <li>Os pontos e cashback são creditados conforme as regras vigentes da empresa onde você compra</li>
    <li>As regras (proporção pontos/real, percentual de cashback, etc.) podem ser alteradas a qualquer momento pela empresa, mediante aviso prévio</li>
    <li>Pontos têm prazo de validade definido por cada empresa</li>
</ul>

<h2>3. Resgate de prêmios</h2>
<ul>
    <li>O resgate só pode ser realizado se houver saldo suficiente de pontos</li>
    <li>Após o resgate, o cliente deve apresentar o código gerado pelo aplicativo no estabelecimento</li>
    <li>Prêmios não são reembolsáveis em dinheiro</li>
</ul>

<h2>4. Conduta do usuário</h2>
<ul>
    <li>É proibido fraudar o sistema, criar contas falsas ou tentar manipular saldos</li>
    <li>Cada cliente pode ter apenas uma conta por estabelecimento</li>
    <li>Contas suspeitas podem ser suspensas ou excluídas sem aviso prévio</li>
</ul>

<h2>5. Indicações</h2>
<p>O sistema de indicações premia clientes que indicam novos cadastros. A premiação só ocorre quando o indicado realiza a primeira compra. Auto-indicações ou indicações fraudulentas não são aceitas.</p>

<h2>6. Cancelamento</h2>
<p>Você pode cancelar sua participação a qualquer momento solicitando o encerramento da conta. Saldo de pontos e cashback não são reembolsáveis em dinheiro no cancelamento.</p>

<h2>7. Alterações nos termos</h2>
<p>Estes termos podem ser atualizados. Mudanças significativas serão comunicadas pelo aplicativo ou WhatsApp.</p>

<p><em>Última atualização: {DATA}</em></p>
HTML;
    }
}
