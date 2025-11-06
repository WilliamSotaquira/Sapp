<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'wsotaquira@movilidadbogota.gov.co',
            'password' => Hash::make('S_07201*'),
        ]);
    }
}
