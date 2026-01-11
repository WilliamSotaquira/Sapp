<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestStoreWithCommaDecimalTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_comma_decimal_estimated_hours_and_web_routes_array(): void
    {
        $user = User::factory()->create();
        $requester = Requester::factory()->create();

        $family = ServiceFamily::create([
            'name' => 'Familia Test',
            'code' => 'FAMT',
            'description' => 'Test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Test',
            'code' => 'SERVT',
            'description' => 'Test',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Subservicio Test',
            'code' => 'SUBT',
            'description' => 'Test',
            'is_active' => true,
            'order' => 0,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA Test',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 4,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'conditions' => null,
            'is_active' => true,
        ]);

        $payload = [
            'requester_id' => $requester->id,
            'title' => 'SR test',
            'description' => 'Descripción de prueba',
            'sub_service_id' => $subService->id,
            'criticality_level' => 'MEDIA',
            'service_id' => $service->id,
            'family_id' => $family->id,
            'sla_id' => $sla->id,
            'requested_by' => $user->id,
            'entry_channel' => 'email_digital',
            // En UI llega como string JSON; aquí probamos array para asegurar normalización
            'web_routes' => ['test-route'],
            'is_reportable' => true,

            'tasks_template' => 'none',
            'tasks' => [
                [
                    'title' => 'Tarea manual',
                    'estimate_mode' => 'manual',
                    // decimal con coma (caso real en navegadores con locale ES)
                    'estimated_hours' => '0,92',
                    'priority' => 'medium',
                    'type' => 'regular',
                    'subtasks' => [
                        ['title' => 'ST 1', 'estimated_minutes' => 25, 'priority' => 'medium'],
                        ['title' => 'ST 2', 'estimated_minutes' => 25, 'priority' => 'medium'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('service-requests.store'), $payload);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_requests', [
            'title' => 'SR test',
            'requester_id' => $requester->id,
            'sub_service_id' => $subService->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarea manual',
            // se guarda como decimal (0.92)
        ]);
    }
}
