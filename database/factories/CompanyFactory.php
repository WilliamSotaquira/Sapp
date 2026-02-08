<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'nit' => fake()->numerify('##########'),
            'status' => 'active',
            'phone' => fake()->numerify('3#########'),
            'address' => fake()->address(),
            'primary_color' => fake()->hexColor(),
            'alternate_color' => fake()->hexColor(),
            'contrast_color' => fake()->hexColor(),
        ];
    }
}
