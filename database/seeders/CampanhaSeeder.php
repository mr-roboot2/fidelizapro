<?php

namespace Database\Seeders;

use App\Models\Campanha;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class CampanhaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Empresa::all() as $empresa) {
            Campanha::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Aniversariantes do mês',
                'mensagem' => "Olá {primeiro_nome}! 🎉 A {empresa} te deseja um feliz aniversário! Você ganhou 100 pontos de presente. Seu saldo atual é de {pontos} pontos.",
                'segmento' => 'aniversariantes',
                'status' => 'concluida',
                'enviada_em' => now()->subDays(5),
                'total_destinatarios' => 12,
                'total_enviados' => 11,
                'total_falhas' => 1,
            ]);

            Campanha::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Volte para a gente!',
                'mensagem' => "Oi {primeiro_nome}, sentimos sua falta na {empresa}. Você tem {pontos} pontos te esperando! 💛",
                'segmento' => 'inativos',
                'status' => 'rascunho',
            ]);

            Campanha::create([
                'empresa_id' => $empresa->id,
                'nome' => 'Promoção de fim de semana',
                'mensagem' => "{primeiro_nome}, neste final de semana, pontos em DOBRO em todas as compras na {empresa}! Aproveite!",
                'segmento' => 'todos',
                'status' => 'agendada',
                'agendada_para' => now()->addDays(2),
            ]);
        }
    }
}
