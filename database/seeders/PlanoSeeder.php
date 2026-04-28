<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            [
                'nome' => 'Starter',
                'descricao' => 'Ideal para pequenos negócios começando',
                'preco_mensal' => 49.90,
                'limite_clientes' => 200,
                'limite_compras_mes' => 500,
                'limite_recompensas' => 10,
                'limite_parceiros' => null,
                'limite_users' => 2,
                'limite_campanhas_mes' => 4,
                'automacoes_disponivel' => true,
                'parceiros_disponivel' => false,
                'white_label_disponivel' => false,
                'ordem' => 1,
            ],
            [
                'nome' => 'Pro',
                'descricao' => 'Para negócios em crescimento',
                'preco_mensal' => 149.90,
                'limite_clientes' => 2000,
                'limite_compras_mes' => 5000,
                'limite_recompensas' => 50,
                'limite_parceiros' => 10,
                'limite_users' => 5,
                'limite_campanhas_mes' => 20,
                'automacoes_disponivel' => true,
                'parceiros_disponivel' => true,
                'white_label_disponivel' => true,
                'ordem' => 2,
            ],
            [
                'nome' => 'Enterprise',
                'descricao' => 'Sem limites para grandes operações',
                'preco_mensal' => 499.90,
                'limite_clientes' => null,
                'limite_compras_mes' => null,
                'limite_recompensas' => null,
                'limite_parceiros' => null,
                'limite_users' => null,
                'limite_campanhas_mes' => null,
                'automacoes_disponivel' => true,
                'parceiros_disponivel' => true,
                'white_label_disponivel' => true,
                'whatsapp_ilimitado' => true,
                'ordem' => 3,
            ],
        ];

        foreach ($planos as $p) {
            Plano::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($p['nome'])], $p);
        }

        // Atribui plano Pro às empresas existentes
        $pro = Plano::where('slug', 'pro')->first();
        if ($pro) {
            Empresa::whereNull('plano_id')->update(['plano_id' => $pro->id]);
        }
    }
}
