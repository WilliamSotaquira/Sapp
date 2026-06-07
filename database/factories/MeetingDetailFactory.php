<?php

namespace Database\Factories;

use App\Models\MeetingDetail;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingDetail>
 */
class MeetingDetailFactory extends Factory
{
    protected $model = MeetingDetail::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'scheduled_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'start_time' => fake()->time('H:i:s'),
            'expected_duration_minutes' => fake()->randomElement([30, 60, 90, 120]),
            'location' => fake()->optional()->address(),
            'virtual_meeting_url' => fake()->optional()->url(),
        ];
    }
}
