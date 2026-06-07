<?php

namespace Database\Factories;

use App\Models\ServiceRequestEvidence;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceRequestEvidence>
 */
class ServiceRequestEvidenceFactory extends Factory
{
    protected $model = ServiceRequestEvidence::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'evidence_type' => fake()->randomElement(['ARCHIVO', 'ACTA', 'ENLACE', 'PASO_A_PASO', 'COMENTARIO']),
            'step_number' => null,
            'file_path' => null,
            'file_original_name' => null,
            'file_mime_type' => null,
            'file_size' => null,
        ];
    }
}
