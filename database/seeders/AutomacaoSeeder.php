<?php

namespace Database\Seeders;

use App\Models\Automacao;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class AutomacaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Empresa::all() as $empresa) {
            foreach (Automacao::TIPOS as $tipo => $nome) {
                Automacao::firstOrCreate(
                    ['empresa_id' => $empresa->id, 'tipo' => $tipo],
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
}
