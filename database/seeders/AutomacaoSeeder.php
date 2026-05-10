<?php

namespace Database\Seeders;

use App\Models\Automacao;
use Illuminate\Database\Seeder;

class AutomacaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Automacao::TIPOS as $tipo => $nome) {
            if ($tipo === 'personalizada') {
                continue;
            }

            Automacao::firstOrCreate(
                ['tipo' => $tipo, 'personalizada' => false],
                [
                    'nome' => $nome,
                    'mensagem' => Automacao::TEMPLATES_PADRAO[$tipo] ?? '',
                    'ativo' => in_array($tipo, ['boas_vindas', 'aniversario', 'pos_compra', 'agradecimento_resgate']),
                    'dias_offset' => $tipo === 'pontos_vencendo' ? 7 : 0,
                ]
            );
        }
    }
}
