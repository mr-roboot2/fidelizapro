<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Recompensa;
use App\Services\ResgateService;
use Illuminate\Database\Seeder;

class ResgateSeeder extends Seeder
{
    public function run(ResgateService $resgateService): void
    {
        Cliente::where('pontos_atual', '>=', 200)->take(20)->get()->each(function (Cliente $cliente) use ($resgateService) {
            $recompensa = Recompensa::where('empresa_id', $cliente->empresa_id)
                ->where('custo_pontos', '<=', $cliente->pontos_atual)
                ->where('ativo', true)
                ->inRandomOrder()
                ->first();

            if (!$recompensa) return;

            try {
                $resgate = $resgateService->solicitar($cliente, $recompensa);
                if (rand(0, 2) > 0) {
                    $resgate->update([
                        'status' => rand(0, 1) ? 'aprovado' : 'entregue',
                        'aprovado_em' => now()->subDays(rand(0, 10)),
                        'entregue_em' => rand(0, 1) ? now()->subDays(rand(0, 5)) : null,
                    ]);
                }
            } catch (\Throwable $e) {}
        });
    }
}
