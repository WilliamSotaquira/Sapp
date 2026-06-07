<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Technician>
 */
class TechnicianFactory extends Factory
{
    protected $model = Technician::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'active',
            'availability_status' => 'available',
        ];
    }
}
