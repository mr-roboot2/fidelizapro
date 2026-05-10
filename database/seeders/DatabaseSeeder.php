<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EmpresaSeeder::class,
            UserSeeder::class,
            ClienteSeeder::class,
            RegraPontuacaoSeeder::class,
            RecompensaSeeder::class,
            CompraSeeder::class,
            ResgateSeeder::class,
            CampanhaSeeder::class,
            PesquisaSeeder::class,
            AutomacaoSeeder::class,
            ParceiroSeeder::class,
            PlanoSeeder::class,
            RoletaSeeder::class,
        ]);
    }
}
