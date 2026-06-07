<?php

namespace Database\Factories;

use App\Models\MeetingParticipant;
use App\Models\MeetingDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingParticipant>
 */
class MeetingParticipantFactory extends Factory
{
    protected $model = MeetingParticipant::class;

    public function definition(): array
    {
        return [
            'meeting_detail_id' => MeetingDetail::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'participante',
            'user_id' => null,
            'attended' => null,
        ];
    }

    public function organizer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'organizador',
        ]);
    }

    public function attended(): static
    {
        return $this->state(fn (array $attributes) => [
            'attended' => true,
        ]);
    }
}
