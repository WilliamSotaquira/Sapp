<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\ServiceRequest;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'task_code' => 'REG-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('###'),
            'type' => 'regular',
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'service_request_id' => ServiceRequest::factory(),
            'technician_id' => null,
            'status' => 'pending',
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'scheduled_date' => now()->addDay(),
        ];
    }

    public function impact(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'impact',
            'task_code' => 'IMP-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('###'),
        ]);
    }

    public function withTechnician(?Technician $technician = null): static
    {
        return $this->state(fn (array $attributes) => [
            'technician_id' => $technician?->id ?? Technician::factory(),
        ]);
    }
}
