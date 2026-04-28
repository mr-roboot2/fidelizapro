<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Services\CompraService;
use Illuminate\Database\Seeder;

class CompraSeeder extends Seeder
{
    public function run(CompraService $compraService): void
    {
        $descricoes = [
            'Compra balcão', 'Atendimento mesa', 'Delivery', 'Retirada loja',
            'Compra programada', 'Pacote serviços',
        ];

        Cliente::all()->each(function (Cliente $cliente) use ($compraService, $descricoes) {
            $compras = rand(0, 8);
            for ($i = 0; $i < $compras; $i++) {
                $compraService->registrar($cliente, [
                    'valor' => rand(2000, 30000) / 100,
                    'descricao' => $descricoes[array_rand($descricoes)],
                    'origem' => 'manual',
                ]);
            }
        });
    }
}
