<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder com senha conhecida só em ambiente local. Em produção o
        // super admin é criado pelo wizard de instalação com senha do operador.
        if (!app()->environment('local')) {
            $this->command?->warn('UserSeeder ignorado fora de ambiente local.');
            return;
        }

        $senhaSuper = Str::random(16);
        User::create([
            'empresa_id' => null,
            'name' => 'Super Admin',
            'email' => 'super@fidelizapro.com',
            'password' => Hash::make($senhaSuper),
            'role' => 'super_admin',
        ]);
        Log::warning("[Seed] Super admin local criado: super@fidelizapro.com / {$senhaSuper}");
        $this->command?->info("Super admin: super@fidelizapro.com / {$senhaSuper}");

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
