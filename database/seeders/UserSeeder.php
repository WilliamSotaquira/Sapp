<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario 1: Administrador
        $admin = User::firstOrCreate(
            ['email' => 'wsotaquira@movilidadbogota.gov.co'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('S_07201*'),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('Usuario Administrador creado exitosamente.');
        } else {
            $this->command->warn('El usuario Administrador ya existe, omitiendo...');
        }

        // Usuario 2: Jersson Hernandez
        $jersson = User::firstOrCreate(
            ['email' => 'jhernandez@movilidadbogota.com'],
            [
                'name' => 'Jersson Hernandez',
                'password' => Hash::make('password'),
            ]
        );

        if ($jersson->wasRecentlyCreated) {
            $this->command->info('Usuario Jersson Hernandez creado exitosamente.');
        } else {
            $this->command->warn('El usuario Jersson Hernandez ya existe, omitiendo...');
        }

    }
}
