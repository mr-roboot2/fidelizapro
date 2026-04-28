<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Recompensa;
use Illuminate\Database\Seeder;

class RecompensaSeeder extends Seeder
{
    public function run(): void
    {
        $catalogos = [
            'pao-quente' => [
                ['Pão Francês — 6 unidades grátis', 100, 'produto', 5.00, true],
                ['Café expresso grátis', 80, 'produto', 4.00, true],
                ['Bolo de aniversário 1kg', 1500, 'produto', 75.00, false],
                ['Cesta café da manhã', 2500, 'produto', 120.00, true],
                ['Desconto de R$ 10', 500, 'desconto', 10.00, false],
            ],
            'beleza-cia' => [
                ['Escova grátis', 800, 'servico', 50.00, true],
                ['Manicure + pedicure', 1200, 'servico', 80.00, true],
                ['Corte feminino', 2000, 'servico', 120.00, false],
                ['Hidratação capilar', 1500, 'servico', 90.00, false],
                ['Kit shampoo + condicionador', 1800, 'produto', 95.00, true],
            ],
            'sabor-da-casa' => [
                ['Sobremesa cortesia', 200, 'produto', 15.00, true],
                ['Entrada do dia', 350, 'produto', 25.00, false],
                ['Jantar para 2 pessoas', 3000, 'experiencia', 180.00, true],
                ['Vinho da casa', 1200, 'produto', 80.00, false],
                ['Desconto de 20% na conta', 800, 'desconto', null, true],
            ],
        ];

        foreach (Empresa::all() as $empresa) {
            $itens = $catalogos[$empresa->slug] ?? $catalogos['pao-quente'];
            foreach ($itens as [$nome, $custo, $tipo, $valor, $destaque]) {
                Recompensa::create([
                    'empresa_id' => $empresa->id,
                    'nome' => $nome,
                    'descricao' => 'Resgate sua recompensa diretamente na loja apresentando o código.',
                    'custo_pontos' => $custo,
                    'estoque' => rand(10, 100),
                    'estoque_inicial' => 100,
                    'tipo' => $tipo,
                    'valor_estimado' => $valor,
                    'destaque' => $destaque,
                    'ativo' => true,
                ]);
            }
        }
    }
}
