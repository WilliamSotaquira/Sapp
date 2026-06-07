<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\RequestType;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Models\Task;
use App\Models\Technician;
use App\Models\User;
use App\Services\TraceabilityChainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityChainServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TraceabilityChainService $service;
    protected array $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TraceabilityChainService();
        $this->context = $this->seedContext();
    }

    private function seedContext(): array
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $company = Company::create([
            'name' => 'Test Company',
            'status' => 'active',
        ]);
        $user->companies()->syncWithoutDetaching([$company->id]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-TEST-001',
            'name' => 'Test Contract',
            'description' => 'Test',
            'is_active' => true,
        ]);
        $company->update(['active_contract_id' => $contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Test Family',
            'code' => 'TST',
            'description' => 'Test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $serviceModel = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Test Service',
            'code' => 'TS',
            'description' => 'Test',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $serviceModel->id,
            'name' => 'Test SubService',
            'code' => 'TSS',
            'description' => 'Test',
            'is_active' => true,
            'order' => 0,
        ]);

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $serviceModel->id,
            'sub_service_id' => $subService->id,
            'name' => 'Test SS',
            'description' => 'Test',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA Test',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 4,
            'availability_percentage' => 99.9,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'is_active' => true,
        ]);

        $requester = Requester::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Requester',
            'email' => 'requester@test.com',
        ]);

        // Seed request types
        RequestType::create(['slug' => 'general', 'name' => 'General', 'is_active' => true]);
        RequestType::create(['slug' => 'reunion', 'name' => 'Reunión', 'is_active' => true]);

        return compact('user', 'company', 'subService', 'sla', 'requester');
    }

    private function createServiceRequest(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::withoutGlobalScopes()->create(array_merge([
            'company_id' => $this->context['company']->id,
            'requester_id' => $this->context['requester']->id,
            'title' => 'Test Request',
            'description' => 'Test description',
            'sub_service_id' => $this->context['subService']->id,
            'sla_id' => $this->context['sla']->id,
            'requested_by' => $this->context['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'PENDIENTE',
            'ticket_number' => 'SR-' . fake()->unique()->numerify('####'),
        ], $overrides));
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TraceabilityChainService::class, $this->service);
    }

    public function test_build_chain_returns_root_node_with_correct_structure(): void
    {
        $requestType = RequestType::where('slug', 'general')->first();

        $sr = $this->createServiceRequest([
            'ticket_number' => 'TEST-001',
            'title' => 'Root Request',
            'request_type_id' => $requestType->id,
            'assigned_to' => $this->context['user']->id,
        ]);

        $result = $this->service->buildChain($sr);

        $this->assertArrayHasKey('ticket_number', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('type_label', $result);
        $this->assertArrayHasKey('assigned_technician', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('is_commitment', $result);
        $this->assertArrayHasKey('children', $result);
        $this->assertArrayHasKey('hidden_children_count', $result);

        $this->assertEquals('TEST-001', $result['ticket_number']);
        $this->assertEquals('Root Request', $result['title']);
        $this->assertEquals('PENDIENTE', $result['status']);
        $this->assertEquals('general', $result['type_label']);
        $this->assertEquals('Test User', $result['assigned_technician']);
        $this->assertFalse($result['is_commitment']);
        $this->assertIsArray($result['children']);
    }

    public function test_build_chain_shows_sin_asignar_when_no_assignee(): void
    {
        $sr = $this->createServiceRequest([
            'assigned_to' => null,
            'request_type_id' => null,
        ]);

        $result = $this->service->buildChain($sr);

        $this->assertEquals('Sin asignar', $result['assigned_technician']);
        $this->assertEquals('general', $result['type_label']);
    }

    public function test_build_chain_includes_children_up_to_max_depth(): void
    {
        $root = $this->createServiceRequest(['ticket_number' => 'ROOT-001']);
        $child1 = $this->createServiceRequest([
            'ticket_number' => 'CHILD-001',
            'service_request_id' => $root->id,
        ]);
        $child2 = $this->createServiceRequest([
            'ticket_number' => 'CHILD-002',
            'service_request_id' => $child1->id,
        ]);
        $child3 = $this->createServiceRequest([
            'ticket_number' => 'CHILD-003',
            'service_request_id' => $child2->id,
        ]);

        $result = $this->service->buildChain($root, 3);

        // Root should have child1
        $this->assertCount(1, $result['children']);
        $this->assertEquals('CHILD-001', $result['children'][0]['ticket_number']);

        // Child1 should have child2
        $this->assertCount(1, $result['children'][0]['children']);
        $this->assertEquals('CHILD-002', $result['children'][0]['children'][0]['ticket_number']);

        // Child2 should have child3
        $this->assertCount(1, $result['children'][0]['children'][0]['children']);
        $this->assertEquals('CHILD-003', $result['children'][0]['children'][0]['children'][0]['ticket_number']);
    }

    public function test_build_chain_truncates_at_max_depth(): void
    {
        $root = $this->createServiceRequest(['ticket_number' => 'ROOT-001']);
        $child1 = $this->createServiceRequest([
            'service_request_id' => $root->id,
        ]);
        $child2 = $this->createServiceRequest([
            'service_request_id' => $child1->id,
        ]);
        $child3 = $this->createServiceRequest([
            'service_request_id' => $child2->id,
        ]);
        // Child4 is beyond depth 3
        $this->createServiceRequest([
            'service_request_id' => $child3->id,
        ]);

        $result = $this->service->buildChain($root, 3);

        // Navigate to depth 3 (child3)
        $depth3Node = $result['children'][0]['children'][0]['children'][0];

        // At max depth, should not recurse further but report hidden count
        $this->assertEmpty($depth3Node['children']);
        $this->assertNotNull($depth3Node['hidden_children_count']);
        $this->assertEquals(1, $depth3Node['hidden_children_count']);
    }

    public function test_get_chain_for_view_returns_null_when_no_parent_and_no_children(): void
    {
        $sr = $this->createServiceRequest([
            'service_request_id' => null,
        ]);

        $result = $this->service->getChainForView($sr);

        $this->assertNull($result);
    }

    public function test_get_chain_for_view_returns_data_when_has_children(): void
    {
        $parent = $this->createServiceRequest();
        $this->createServiceRequest([
            'service_request_id' => $parent->id,
        ]);

        $result = $this->service->getChainForView($parent);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('children', $result);
    }

    public function test_get_chain_for_view_returns_data_when_has_parent(): void
    {
        $parent = $this->createServiceRequest();
        $child = $this->createServiceRequest([
            'service_request_id' => $parent->id,
        ]);

        $result = $this->service->getChainForView($child);

        $this->assertNotNull($result);
    }

    public function test_get_chain_for_view_includes_commitments_as_nodes(): void
    {
        $techUser = User::factory()->create(['name' => 'Task Technician']);
        $technician = Technician::create([
            'user_id' => $techUser->id,
            'status' => 'active',
            'availability_status' => 'available',
        ]);

        $parent = $this->createServiceRequest();
        $this->createServiceRequest([
            'service_request_id' => $parent->id,
        ]);

        // Create an impact task (commitment)
        Task::create([
            'service_request_id' => $parent->id,
            'type' => 'impact',
            'title' => 'Test Commitment',
            'status' => 'pending',
            'technician_id' => $technician->id,
            'task_code' => 'IMP-' . now()->format('Ymd') . '-001',
            'priority' => 'medium',
            'scheduled_date' => now()->addDay(),
        ]);

        $result = $this->service->getChainForView($parent);

        $this->assertNotNull($result);

        // Find the commitment node in children
        $commitmentNodes = array_filter($result['children'], fn($node) => $node['is_commitment'] === true);
        $this->assertNotEmpty($commitmentNodes);

        $commitment = array_values($commitmentNodes)[0];
        $this->assertEquals('Test Commitment', $commitment['title']);
        $this->assertEquals('compromiso', $commitment['type_label']);
        $this->assertTrue($commitment['is_commitment']);
        $this->assertEquals('Task Technician', $commitment['assigned_technician']);
    }

    public function test_commitment_nodes_show_sin_asignar_when_no_technician(): void
    {
        $parent = $this->createServiceRequest();
        $this->createServiceRequest([
            'service_request_id' => $parent->id,
        ]);

        Task::create([
            'service_request_id' => $parent->id,
            'type' => 'impact',
            'title' => 'Unassigned Commitment',
            'status' => 'pending',
            'technician_id' => null,
            'task_code' => 'IMP-' . now()->format('Ymd') . '-002',
            'priority' => 'medium',
            'scheduled_date' => now()->addDay(),
        ]);

        $result = $this->service->getChainForView($parent);

        $commitmentNodes = array_filter($result['children'], fn($node) => $node['is_commitment'] === true);
        $commitment = array_values($commitmentNodes)[0];

        $this->assertEquals('Sin asignar', $commitment['assigned_technician']);
    }
}
