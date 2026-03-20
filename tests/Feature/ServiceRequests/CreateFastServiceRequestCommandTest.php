<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFastServiceRequestCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedServiceTree(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Movilidad Test',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-FAST-001',
            'name' => 'Contrato rapido',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte marzo',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'created_by' => $user->id,
        ]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia SEO',
            'code' => 'FSEO',
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio SEO',
            'code' => 'SSEO',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Actualizacion SEO',
            'code' => 'SEO_TEC',
            'description' => 'Subservicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA BAJA',
            'criticality_level' => 'BAJA',
            'response_time_hours' => 4,
            'resolution_time_hours' => 8,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 120,
            'response_time_minutes' => 240,
            'resolution_time_minutes' => 480,
            'conditions' => 'Test',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA MEDIA',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 2,
            'resolution_time_hours' => 6,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 60,
            'response_time_minutes' => 120,
            'resolution_time_minutes' => 360,
            'conditions' => 'Test',
            'is_active' => true,
        ]);

        Requester::factory()->create([
            'company_id' => $company->id,
            'name' => 'Jimena Delgado Soto',
            'email' => 'jdelgados@example.com',
        ]);

        return compact('user', 'company', 'subService', 'sla');
    }

    public function test_dry_run_resolves_context_without_creating_request(): void
    {
        $data = $this->seedServiceTree();

        $payload = [
            'company_name' => $data['company']->name,
            'sub_service_code' => 'SEO_TEC',
            'requester_name' => 'Jimena Delgado Soto',
            'requester_email' => 'jdelgados@example.com',
            'title' => 'Posicionamiento SEO OMB',
            'description' => 'Se requiere revisar el posicionamiento SEO del Observatorio de Movilidad.',
            'criticality_level' => 'MEDIA',
            'entry_channel' => 'email_corporativo',
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
            'tasks' => [
                [
                    'title' => 'Analizar visibilidad organica',
                    'description' => 'Analizar indexacion y metadatos actuales.',
                    'priority' => 'medium',
                    'type' => 'regular',
                    'subtasks' => [
                        [
                            'title' => 'Revisar indexacion inicial (20 min)',
                            'notes' => 'Validar presencia basica en buscadores.',
                            'priority' => 'medium',
                            'estimated_minutes' => 20,
                        ],
                    ],
                ],
            ],
        ];

        $this->artisan('service-requests:create-fast', [
            '--json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            '--dry-run' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"dry_run": true');

        $this->assertDatabaseCount('service_requests', 0);
        $this->assertDatabaseHas('requesters', [
            'company_id' => $data['company']->id,
            'email' => 'jdelgados@example.com',
        ]);
    }

    public function test_command_creates_request_and_blocks_duplicate_by_default(): void
    {
        $data = $this->seedServiceTree();

        $payload = [
            'company_name' => $data['company']->name,
            'sub_service_code' => 'SEO_TEC',
            'requester_name' => 'Jimena Delgado Soto',
            'requester_email' => 'jdelgados@example.com',
            'title' => 'Posicionamiento SEO OMB',
            'description' => 'Se requiere revisar el posicionamiento SEO del Observatorio de Movilidad.',
            'criticality_level' => 'MEDIA',
            'entry_channel' => 'email_corporativo',
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
            'tasks' => [
                [
                    'title' => 'Analizar visibilidad organica',
                    'description' => 'Analizar indexacion y metadatos actuales.',
                    'priority' => 'medium',
                    'type' => 'regular',
                    'subtasks' => [
                        [
                            'title' => 'Revisar indexacion inicial (20 min)',
                            'notes' => 'Validar presencia basica en buscadores.',
                            'priority' => 'medium',
                            'estimated_minutes' => 20,
                        ],
                    ],
                ],
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $this->artisan('service-requests:create-fast', [
            '--json' => $json,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"created": true');

        $this->assertDatabaseCount('service_requests', 1);

        $created = ServiceRequest::withoutGlobalScopes()->first();
        $this->assertNotNull($created);
        $this->assertSame('Posicionamiento SEO OMB', $created->title);
        $this->assertSame($data['subService']->id, $created->sub_service_id);
        $this->assertSame($data['sla']->id, $created->sla_id);
        $this->assertSame(1, $created->tasks()->count());

        $this->artisan('service-requests:create-fast', [
            '--json' => $json,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"duplicate": true');

        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_command_generates_default_tasks_when_not_provided(): void
    {
        $data = $this->seedServiceTree();

        $payload = [
            'company_name' => $data['company']->name,
            'sub_service_code' => 'SEO_TEC',
            'requester_name' => 'Jimena Delgado Soto',
            'requester_email' => 'jdelgados@example.com',
            'title' => 'Posicionamiento SEO OMB',
            'description' => 'Se requiere revisar el posicionamiento SEO del Observatorio de Movilidad.',
            'criticality_level' => 'MEDIA',
            'entry_channel' => 'email_corporativo',
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
        ];

        $this->artisan('service-requests:create-fast', [
            '--json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ])->assertExitCode(0);

        $created = ServiceRequest::withoutGlobalScopes()->first();
        $this->assertNotNull($created);
        $this->assertSame(1, $created->tasks()->count());
        $this->assertSame(3, $created->tasks()->withCount('subtasks')->first()->subtasks_count);
    }

    public function test_command_infers_company_and_subservice_when_missing(): void
    {
        $data = $this->seedServiceTree();

        $payload = [
            'requester_name' => 'Jimena Delgado Soto',
            'requester_email' => 'jdelgados@movilidadbogota.gov.co',
            'title' => 'Posicionamiento SEO OMB',
            'description' => 'Se requiere revisar el posicionamiento SEO del Observatorio de Movilidad porque no aparece al buscarlo.',
            'criticality_level' => 'MEDIA',
            'entry_channel' => 'email_corporativo',
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
        ];

        $this->artisan('service-requests:create-fast', [
            '--json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            '--dry-run' => true,
        ])->assertExitCode(0);
    }
}
