<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('ClienteSeeder ignorado fora de ambiente local.');
            return;
        }

        $nomes = [
            'Maria Silva', 'João Souza', 'Ana Costa', 'Pedro Almeida', 'Juliana Lima',
            'Carlos Oliveira', 'Fernanda Pereira', 'Ricardo Santos', 'Patrícia Rodrigues',
            'Eduardo Martins', 'Camila Ferreira', 'Bruno Carvalho', 'Larissa Gomes',
            'Marcelo Barbosa', 'Aline Ribeiro', 'Thiago Araújo', 'Beatriz Cardoso',
            'Rafael Mendes', 'Vanessa Castro', 'Gustavo Pinto',
        ];

        $sobrenomes = ['da Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Almeida', 'Costa'];

        foreach (Empresa::all() as $empresa) {
            $i = 0;
            foreach ($nomes as $nome) {
                $telefone = '(11) 9'.rand(1000, 9999).'-'.str_pad((string) ($i + $empresa->id * 100), 4, '0', STR_PAD_LEFT);

                Cliente::create([
                    'empresa_id' => $empresa->id,
                    'nome' => $nome.' '.$sobrenomes[array_rand($sobrenomes)],
                    'telefone' => $telefone,
                    'email' => strtolower(str_replace(' ', '.', $nome)).$empresa->id.'@email.com',
                    'cpf' => $this->gerarCpfFake(),
                    'data_nascimento' => now()->subYears(rand(20, 60))->subDays(rand(0, 364))->toDateString(),
                    'password' => Hash::make('123456'),
                    'pontos_atual' => rand(0, 5000),
                    'cashback_atual' => rand(0, 10000) / 100,
                    'total_gasto' => rand(100, 50000) / 10,
                    'total_compras' => rand(0, 50),
                    'ultima_compra' => now()->subDays(rand(0, 90)),
                    'aceita_whatsapp' => rand(0, 10) > 1,
                ]);
                $i++;
            }
        }
    }

    protected function gerarCpfFake(): string
    {
        return rand(100, 999).'.'.rand(100, 999).'.'.rand(100, 999).'-'.rand(10, 99);
    }
}
