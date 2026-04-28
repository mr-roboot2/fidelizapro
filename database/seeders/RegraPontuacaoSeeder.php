<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\RegraPontuacao;
use Illuminate\Database\Seeder;

class RegraPontuacaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Empresa::all() as $empresa) {
            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Pontuação padrão',
                'tipo' => 'compra',
                'valor_minimo' => 0,
                'valor_maximo' => null,
                'pontos_por_real' => $empresa->pontos_por_real,
                'multiplicador' => 1,
                'ativo' => true,
            ]);

            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Compras acima de R$ 100 — pontos em dobro',
                'tipo' => 'compra',
                'valor_minimo' => 100,
                'valor_maximo' => null,
                'pontos_por_real' => $empresa->pontos_por_real,
                'multiplicador' => 2,
                'ativo' => true,
            ]);

            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Bônus de aniversário',
                'tipo' => 'aniversario',
                'pontos_fixos' => 100,
                'multiplicador' => 1,
                'ativo' => true,
            ]);

            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Indicação de amigo',
                'tipo' => 'indicacao',
                'pontos_fixos' => 50,
                'multiplicador' => 1,
                'ativo' => true,
            ]);

            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Bônus de cadastro',
                'tipo' => 'cadastro',
                'pontos_fixos' => 20,
                'multiplicador' => 1,
                'ativo' => true,
            ]);

            RegraPontuacao::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Bônus por avaliação',
                'tipo' => 'avaliacao',
                'pontos_fixos' => 10,
                'multiplicador' => 1,
                'ativo' => true,
            ]);
        }
    }
}
