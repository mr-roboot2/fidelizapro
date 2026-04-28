<?php

namespace Database\Seeders;

use App\Models\Beneficio;
use App\Models\Empresa;
use App\Models\Parceiro;
use Illuminate\Database\Seeder;

class ParceiroSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            ['Auto Posto Central', 'Combustível', '5% desconto na gasolina', 'desconto_percentual', 5, 'Apresentar cupom antes do abastecimento. Limite 50L.'],
            ['Farmácia Vida', 'Farmácia', '10% desconto em medicamentos', 'desconto_percentual', 10, 'Não cumulativo. Não válido para genéricos com tabela.'],
            ['Estética Glamour', 'Beleza', 'Limpeza de pele cortesia', 'cortesia', null, 'Mediante agendamento. 1ª sessão.'],
            ['Lava Jato Express', 'Automotivo', 'Lavagem completa por R$ 25', 'desconto_valor', 15, 'Inclui aspiração e cera. Reservar pelo telefone.'],
            ['Pizzaria Trattoria', 'Restaurante', 'Pizza grande pelo preço da média', 'cortesia', null, 'Válido jantar terças e quartas.'],
            ['Academia FitLife', 'Esporte', 'Aula experimental grátis', 'servico_gratis', null, 'Apresentar identidade. 1 cupom por mês.'],
        ];

        foreach (Empresa::all() as $empresa) {
            // 3 parceiros por empresa
            $selecionados = collect($catalogo)->shuffle()->take(3);

            foreach ($selecionados as [$nome, $categoria, $beneficioNome, $tipo, $valor, $condicoes]) {
                $parceiro = Parceiro::create([
                    'empresa_id' => $empresa->id,
                    'nome' => $nome,
                    'categoria' => $categoria,
                    'descricao' => "Parceiro da {$empresa->nome} oferecendo benefícios exclusivos para nossos clientes fiéis.",
                    'telefone' => '(11) '.rand(2000, 9999).'-'.rand(1000, 9999),
                    'email' => strtolower(str_replace(' ', '', $nome)).'@parceiro.com',
                    'endereco' => 'Rua Exemplo, '.rand(10, 999),
                ]);

                Beneficio::create([
                    'parceiro_id' => $parceiro->id,
                    'nome' => $beneficioNome,
                    'descricao' => 'Benefício exclusivo para clientes da '.$empresa->nome,
                    'tipo' => $tipo,
                    'valor' => $valor,
                    'condicoes' => $condicoes,
                    'valido_ate' => now()->addMonths(6),
                    'limite_por_cliente' => 1,
                    'destaque' => rand(0, 1) === 1,
                ]);
            }
        }
    }
}
