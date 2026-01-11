<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ServiceFamilySeeder::class,
            ServiceSeeder::class,
            SubServiceSeeder::class,
            ServiceSubserviceSeeder::class,
            SLASeeder::class,

        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Datos DEMO para pruebas end-to-end (solo local/testing)
        if (app()->environment(['local', 'testing'])) {
            $this->call(ServiceRequestsDemoSeeder::class);
        }
    }
}
