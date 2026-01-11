<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\Task;
use App\Models\User;
use App\Services\ServiceRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTasksSubtasksEstimationTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimalServiceTree(): array
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

        return compact('user', 'requester', 'family', 'service', 'subService', 'sla');
    }

    public function test_creates_tasks_and_subtasks_and_calculates_estimated_hours_from_subtasks(): void
    {
        $data = $this->seedMinimalServiceTree();

        /** @var ServiceRequestService $service */
        $service = app(ServiceRequestService::class);

        $serviceRequest = $service->createServiceRequest([
            'requester_id' => $data['requester']->id,
            'title' => 'SR con subtareas',
            'description' => 'Descripción de prueba',
            'sub_service_id' => $data['subService']->id,
            'criticality_level' => 'MEDIA',
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => ServiceRequest::ENTRY_CHANNEL_DIGITAL_EMAIL,
            'web_routes' => json_encode(['test-route']),
            'is_reportable' => true,

            'tasks_template' => 'none',
            'tasks' => [
                [
                    'title' => 'Tarea auto',
                    'estimate_mode' => 'auto',
                    'estimated_hours' => '',
                    'priority' => 'medium',
                    'type' => 'regular',
                    'subtasks' => [
                        ['title' => 'ST 1', 'estimated_minutes' => 30, 'priority' => 'medium'],
                        ['title' => 'ST 2', 'estimated_minutes' => 45, 'priority' => 'high'],
                        ['title' => '', 'estimated_minutes' => 999, 'priority' => 'low'], // debe ignorarse
                    ],
                ],
                [
                    'title' => 'Tarea manual',
                    'estimate_mode' => 'manual',
                    'estimated_hours' => 1.50,
                    'priority' => 'medium',
                    'type' => 'regular',
                    'subtasks' => [
                        ['title' => 'ST A', 'estimated_minutes' => 20, 'priority' => 'medium'],
                        ['title' => 'ST B', 'estimated_minutes' => 20, 'priority' => 'medium'],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'title' => 'SR con subtareas',
        ]);

        $tasks = Task::where('service_request_id', $serviceRequest->id)->orderBy('id')->get();
        $this->assertCount(2, $tasks);

        $autoTask = $tasks->firstWhere('title', 'Tarea auto');
        $manualTask = $tasks->firstWhere('title', 'Tarea manual');

        $this->assertNotNull($autoTask);
        $this->assertNotNull($manualTask);

        // Auto: 30 + 45 = 75 min => 1.25h
        $this->assertEqualsWithDelta(1.25, (float) $autoTask->estimated_hours, 0.01);

        // Siempre basado en subtareas: 20 + 20 = 40 min => 0.67h
        $this->assertEqualsWithDelta(0.67, (float) $manualTask->estimated_hours, 0.01);

        // Subtareas: se deben crear solo las de título no vacío
        $this->assertCount(2, $autoTask->subtasks()->get());
        $this->assertCount(2, $manualTask->subtasks()->get());
    }
}
