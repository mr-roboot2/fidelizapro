<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        Empresa::create([
            'nome' => 'Padaria Pão Quente',
            'slug' => 'pao-quente',
            'cnpj' => '12.345.678/0001-90',
            'telefone' => '(11) 3000-0001',
            'email' => 'contato@paoquente.com.br',
            'endereco' => 'Rua das Flores, 100 - São Paulo/SP',
            'cor_primaria' => '#f59e0b',
            'cor_secundaria' => '#fbbf24',
            'pontos_por_real' => 1.00,
            'cashback_percentual' => 2.00,
            'validade_pontos_dias' => 365,
        ]);

        Empresa::create([
            'nome' => 'Salão Beleza & Cia',
            'slug' => 'beleza-cia',
            'cnpj' => '98.765.432/0001-10',
            'telefone' => '(11) 3000-0002',
            'email' => 'contato@belezacia.com.br',
            'endereco' => 'Av. Paulista, 1500 - São Paulo/SP',
            'cor_primaria' => '#ec4899',
            'cor_secundaria' => '#f472b6',
            'pontos_por_real' => 2.00,
            'cashback_percentual' => 5.00,
            'validade_pontos_dias' => 180,
        ]);

        Empresa::create([
            'nome' => 'Restaurante Sabor da Casa',
            'slug' => 'sabor-da-casa',
            'cnpj' => '11.222.333/0001-44',
            'telefone' => '(11) 3000-0003',
            'email' => 'reservas@saborcasa.com.br',
            'endereco' => 'Rua do Comércio, 50 - São Paulo/SP',
            'cor_primaria' => '#dc2626',
            'cor_secundaria' => '#ef4444',
            'pontos_por_real' => 1.50,
            'cashback_percentual' => 3.00,
            'validade_pontos_dias' => 365,
        ]);
    }
}
