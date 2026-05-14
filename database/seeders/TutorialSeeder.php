<?php

namespace Database\Seeders;

use App\Models\Tutorial;
use Illuminate\Database\Seeder;

class TutorialSeeder extends Seeder
{
    public function run(): void
    {
        $tutoriais = [
            [
                'titulo'    => 'Visão geral do painel',
                'descricao' => "Conheça as principais áreas do sistema: dashboard, caixa rápido, clientes e recompensas. Comece por aqui se for sua primeira vez.",
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'duracao'   => '3:45',
                'ordem'     => 10,
            ],
            [
                'titulo'    => 'Como usar o Caixa rápido',
                'descricao' => "Lance uma compra em segundos: digite o telefone do cliente, o valor, e os pontos/cashback são creditados automaticamente. Também explica o cupom de venda.",
                'video_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
                'duracao'   => '2:30',
                'ordem'     => 20,
            ],
            [
                'titulo'    => 'Cadastrando clientes e ajustando pontos',
                'descricao' => "Como criar um cliente novo direto pelo caixa, importar uma planilha, e ajustar pontos/cashback manualmente quando precisar corrigir.",
                'video_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0',
                'duracao'   => '4:10',
                'ordem'     => 30,
            ],
            [
                'titulo'    => 'Configurando regras de pontuação',
                'descricao' => "Defina quantos pontos o cliente ganha por real gasto, regras especiais por categoria de produto e bônus em dias específicos.",
                'video_url' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk',
                'duracao'   => '5:20',
                'ordem'     => 40,
            ],
            [
                'titulo'    => 'Criando recompensas e gerenciando resgates',
                'descricao' => "Cadastre prêmios que o cliente pode trocar pelos pontos. Aprenda também a aprovar, entregar e cancelar resgates.",
                'video_url' => 'https://www.youtube.com/watch?v=hT_nvWreIhg',
                'duracao'   => '6:15',
                'ordem'     => 50,
            ],
            [
                'titulo'    => 'Roleta da sorte: configuração e gatilhos',
                'descricao' => "Configure os prêmios da roleta, defina probabilidades e crie gatilhos automáticos (ex: aniversário, dia fraco, cliente VIP).",
                'video_url' => 'https://www.youtube.com/watch?v=L_jWHffIx5E',
                'duracao'   => '7:40',
                'ordem'     => 60,
            ],
            [
                'titulo'    => 'Sorteios entre clientes',
                'descricao' => "Crie um sorteio, distribua bilhetes pelos clientes (manual ou via roleta) e faça o sorteio na hora.",
                'video_url' => 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ',
                'duracao'   => '3:55',
                'ordem'     => 70,
            ],
            [
                'titulo'    => 'Cashback: como funciona',
                'descricao' => "Diferença entre pontos e cashback, como o cliente acumula e usa o saldo no caixa. Ideal pra negócios que preferem desconto direto.",
                'video_url' => 'https://www.youtube.com/watch?v=ZZ5LpwO-An4',
                'duracao'   => '4:00',
                'ordem'     => 80,
            ],
            [
                'titulo'    => 'Parceiros: oferecendo benefícios externos',
                'descricao' => "Cadastre parceiros (lojas vizinhas, serviços) que dão benefícios pros seus clientes. Eles validam o cupom por URL com secret.",
                'video_url' => 'https://www.youtube.com/watch?v=YQHsXMglC9A',
                'duracao'   => '5:00',
                'ordem'     => 90,
            ],
            [
                'titulo'    => 'AI Growth: relatórios e insights',
                'descricao' => "Use a IA pra entender o comportamento dos seus clientes: quem está sumindo, quem é VIP, qual recompensa converte mais.",
                'video_url' => 'https://www.youtube.com/watch?v=CevxZvSJLk8',
                'duracao'   => '8:20',
                'ordem'     => 100,
            ],
            [
                'titulo'    => 'Compartilhando o app PWA com clientes',
                'descricao' => "Gere o QR code, cartaz pra imprimir e o link curto pros clientes instalarem o app da sua loja no celular.",
                'video_url' => 'https://www.youtube.com/watch?v=RgKAFK5djSk',
                'duracao'   => '2:15',
                'ordem'     => 110,
            ],
            [
                'titulo'    => 'WhatsApp: templates e automações',
                'descricao' => "Veja como o sistema envia mensagens automáticas pelos clientes em eventos como nova compra, aniversário e clientes sumidos.",
                'video_url' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0',
                'duracao'   => '6:30',
                'ordem'     => 120,
            ],
        ];

        foreach ($tutoriais as $dados) {
            Tutorial::updateOrCreate(
                ['titulo' => $dados['titulo']],
                array_merge($dados, [
                    'tipo_video' => 'url',
                    'publicado'  => true,
                ])
            );
        }
    }
}
