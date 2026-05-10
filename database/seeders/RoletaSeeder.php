<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Recompensa;
use App\Models\Roleta;
use App\Models\RoletaPremio;
use Illuminate\Database\Seeder;

class RoletaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Empresa::all() as $empresa) {
            $roleta = Roleta::firstOrCreate(
                ['empresa_id' => $empresa->id],
                ['nome' => 'Roleta da Sorte', 'ativa' => true]
            );

            if ($roleta->premios()->exists()) {
                continue;
            }

            $recompensa = Recompensa::where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->orderBy('custo_pontos')
                ->first();

            $premios = [
                ['ordem' => 0, 'label' => '100 pts',     'cor' => '#f59e0b', 'tipo' => 'pontos',      'pontos' => 100, 'peso' => 8],
                ['ordem' => 1, 'label' => 'Tente de novo','cor' => '#94a3b8', 'tipo' => 'nada',                          'peso' => 8],
                ['ordem' => 2, 'label' => '30 pts',      'cor' => '#3b82f6', 'tipo' => 'pontos',      'pontos' => 30,  'peso' => 18],
                ['ordem' => 3, 'label' => '+1 giro',     'cor' => '#10b981', 'tipo' => 'nova_chance',                  'peso' => 12],
                ['ordem' => 4, 'label' => '10 pts',      'cor' => '#6366f1', 'tipo' => 'pontos',      'pontos' => 10,  'peso' => 25],
                ['ordem' => 5, 'label' => '50 pts',      'cor' => '#a855f7', 'tipo' => 'pontos',      'pontos' => 50,  'peso' => 14],
                ['ordem' => 6, 'label' => 'Tente de novo','cor' => '#64748b', 'tipo' => 'nada',                          'peso' => 10],
            ];

            if ($recompensa) {
                $premios[] = ['ordem' => 7, 'label' => $recompensa->nome, 'cor' => '#ef4444', 'tipo' => 'recompensa', 'recompensa_id' => $recompensa->id, 'peso' => 5];
            } else {
                $premios[] = ['ordem' => 7, 'label' => '200 pts', 'cor' => '#ef4444', 'tipo' => 'pontos', 'pontos' => 200, 'peso' => 5];
            }

            foreach ($premios as $p) {
                $roleta->premios()->create($p + ['ativo' => true]);
            }
        }
    }
}
