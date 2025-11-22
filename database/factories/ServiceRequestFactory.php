<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ServiceRequest;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ServiceRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'SR-' . fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'PENDIENTE',
            'criticality_level' => fake()->randomElement(['BAJA', 'MEDIA', 'ALTA', 'CRITICA']),
            'requester_id' => 1, // Usaremos un ID fijo para testing
            'sub_service_id' => 1,
            'sla_id' => 1,
            'requested_by' => 1,
            'entry_channel' => fake()->randomElement(ServiceRequest::getEntryChannelValidationValues()),
            'web_routes' => json_encode(['test-route']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Estado pendiente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDIENTE',
        ]);
    }

    /**
     * Estado resuelto
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'RESUELTA',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Criticidad crítica
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'criticality_level' => 'CRITICA',
        ]);
    }
}
