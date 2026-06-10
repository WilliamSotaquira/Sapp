<?php

namespace Database\Factories;

use App\Models\Cut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cut>
 */
class CutFactory extends Factory
{
    protected $model = Cut::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '-1 month');
        $endDate = (clone $startDate)->modify('+1 month');

        return [
            'contract_id' => 1,
            'name' => 'Corte ' . fake()->unique()->numberBetween(1, 1000),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => fake()->optional()->sentence(),
            'folder_path' => null,
            'created_by' => 1,
        ];
    }
}
