<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Pesquisa;
use Illuminate\Database\Seeder;

class PesquisaSeeder extends Seeder
{
    public function run(): void
    {
        $comentarios = [
            'Excelente atendimento, voltarei sempre!',
            'Produtos sempre frescos e bem servidos.',
            'Demorou um pouco, mas valeu a pena.',
            'Muito bom! Recomendo a todos.',
            'Atendimento poderia ser mais rápido.',
            null, null, null,
        ];

        Compra::inRandomOrder()->take(50)->get()->each(function (Compra $compra) use ($comentarios) {
            Pesquisa::create([
                'empresa_id' => $compra->empresa_id,
                'cliente_id' => $compra->cliente_id,
                'compra_id' => $compra->id,
                'nota' => rand(1, 5),
                'comentario' => $comentarios[array_rand($comentarios)],
                'created_at' => $compra->created_at->addHours(rand(1, 48)),
            ]);
        });
    }
}
