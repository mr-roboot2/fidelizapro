<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'empresa_id' => null,
            'name' => 'Super Admin',
            'email' => 'super@fidelizapro.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        foreach (Empresa::all() as $empresa) {
            User::create([
                'empresa_id' => $empresa->id,
                'name' => 'Admin '.$empresa->nome,
                'email' => 'admin@'.$empresa->slug.'.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]);

            User::create([
                'empresa_id' => $empresa->id,
                'name' => 'Atendente '.$empresa->nome,
                'email' => 'atendente@'.$empresa->slug.'.com',
                'password' => Hash::make('password'),
                'role' => 'atendente',
            ]);
        }
    }
}
