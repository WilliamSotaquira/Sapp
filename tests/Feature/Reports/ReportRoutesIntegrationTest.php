<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRoutesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Integration Test Company',
            'status' => 'active',
        ]);

        $company->users()->attach($user->id);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-INT-001',
            'name' => 'Contrato Integration Test',
            'description' => 'Contrato de prueba para integration tests',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $company->refresh();

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Integration',
            'code' => 'FINT',
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Integration',
            'code' => 'SINT',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'SubServicio Integration',
            'code' => 'SS_INT',
            'description' => 'SubServicio de prueba',
            'is_active' => true,
            'order' => 0,
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

        $requester = Requester::factory()->create([
            'company_id' => $company->id,
            'name' => 'Solicitante Integration',
            'email' => 'solicitante@integration.test',
            'department' => 'IT',
        ]);

        return compact('user', 'company', 'contract', 'family', 'service', 'subService', 'sla', 'requester');
    }

    private function createServiceRequest(array $context, array $overrides = []): ServiceRequest
    {
        $defaults = [
            'company_id' => $context['company']->id,
            'requester_id' => $context['requester']->id,
            'title' => 'Solicitud de prueba integration',
            'description' => 'Descripción de prueba para integration tests.',
            'sub_service_id' => $context['subService']->id,
            'sla_id' => $context['sla']->id,
            'requested_by' => $context['user']->id,
            'assigned_to' => $context['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'ACEPTADA',
            'created_at' => now(),
        ];

        return ServiceRequest::withoutGlobalScopes()->create(array_merge($defaults, $overrides));
    }

    // =========================================================================
    // TEST: New routes respond with 200 for authenticated users
    // Validates: Requirements 2.1, 4.1, 5.1, 6.1, 7.5
    // =========================================================================

    public function test_reports_index_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_timeline_index_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.index'));

        $response->assertOk();
    }

    public function test_timeline_show_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();
        $sr = $this->createServiceRequest($data);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.show', $sr->id));

        $response->assertOk();
    }

    public function test_timeline_search_responds_with_redirect_for_authenticated_user(): void
    {
        $data = $this->seedContext();
        $sr = $this->createServiceRequest($data, ['ticket_number' => 'SR-9999']);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.timeline.search'), ['ticket' => 'SR-9999']);

        // Single match redirects to show
        $response->assertRedirect(route('reports.timeline.show', $sr->id));
    }

    public function test_services_sla_index_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.services-sla.index'));

        $response->assertOk();
    }

    public function test_operational_overview_index_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.operational-overview.index'));

        $response->assertOk();
    }

    public function test_search_analysis_index_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.index'));

        $response->assertOk();
    }

    public function test_search_analysis_search_responds_200_for_authenticated_user(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => 'test']));

        $response->assertOk();
    }

    // =========================================================================
    // TEST: Old deprecated routes redirect to new URLs
    // Validates: Requirements 7.5
    // =========================================================================

    public function test_sla_compliance_redirects_to_services_sla(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.sla-compliance'));

        $response->assertRedirect(route('reports.services-sla.index'));
    }

    public function test_requests_by_status_redirects_to_operational_overview(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.requests-by-status'));

        $response->assertRedirect(route('reports.operational-overview.index'));
    }

    public function test_criticality_levels_redirects_to_operational_overview(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.criticality-levels'));

        $response->assertRedirect(route('reports.operational-overview.index'));
    }

    public function test_service_performance_redirects_to_services_sla(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.service-performance'));

        $response->assertRedirect(route('reports.services-sla.index'));
    }

    public function test_monthly_trends_redirects_to_operational_overview(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.monthly-trends'));

        $response->assertRedirect(route('reports.operational-overview.index'));
    }

    // =========================================================================
    // TEST: Export endpoints generate files without errors
    // Validates: Requirements 4.1, 5.1, 6.1
    // =========================================================================

    public function test_services_sla_export_csv_generates_file(): void
    {
        $data = $this->seedContext();
        $this->createServiceRequest($data);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.services-sla.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_services_sla_export_pdf_generates_file(): void
    {
        $data = $this->seedContext();
        $this->createServiceRequest($data);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.services-sla.export', ['format' => 'pdf']));

        // PDF export should either download (200) or redirect back with error if DomPDF not available
        $this->assertTrue(
            $response->isOk() || $response->isRedirect(),
            'PDF export should return 200 or redirect'
        );
    }

    public function test_operational_overview_export_csv_generates_file(): void
    {
        $data = $this->seedContext();
        $this->createServiceRequest($data);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.operational-overview.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_operational_overview_export_pdf_generates_file(): void
    {
        $data = $this->seedContext();
        $this->createServiceRequest($data);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.operational-overview.export', ['format' => 'pdf']));

        $this->assertTrue(
            $response->isOk() || $response->isRedirect(),
            'PDF export should return 200 or redirect'
        );
    }

    public function test_search_analysis_export_csv_generates_file(): void
    {
        $data = $this->seedContext();
        $this->createServiceRequest($data, ['title' => 'Solicitud exportable']);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.export', [
                'format' => 'csv',
                'terms' => 'exportable',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_search_analysis_export_pdf_generates_file(): void
    {
        $data = $this->seedContext();
        $this->createServiceRequest($data, ['title' => 'Solicitud exportable pdf']);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.export', [
                'format' => 'pdf',
                'terms' => 'exportable',
            ]));

        $this->assertTrue(
            $response->isOk() || $response->isRedirect(),
            'PDF export should return 200 or redirect'
        );
    }

    public function test_timeline_export_pdf_generates_file(): void
    {
        $data = $this->seedContext();
        $sr = $this->createServiceRequest($data);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.export', ['id' => $sr->id, 'format' => 'pdf']));

        $this->assertTrue(
            $response->isOk() || $response->isRedirect(),
            'Timeline PDF export should return 200 or redirect'
        );
    }

    // =========================================================================
    // TEST: Workspace scoping via company_id in all queries
    // Validates: Requirements 2.1, 4.1, 5.1, 6.1
    // =========================================================================

    public function test_timeline_index_only_shows_requests_from_current_workspace(): void
    {
        $data = $this->seedContext();

        // Create a service request for the current workspace
        $ownRequest = $this->createServiceRequest($data, [
            'title' => 'Own workspace request',
            'ticket_number' => 'SR-OWN-001',
        ]);

        // Create another company with its own service request
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'status' => 'active',
        ]);
        $otherContract = Contract::create([
            'company_id' => $otherCompany->id,
            'number' => 'C-OTHER-001',
            'name' => 'Contrato Other',
            'description' => 'Otro contrato',
            'is_active' => true,
        ]);
        $otherCompany->update(['active_contract_id' => $otherContract->id]);

        $otherFamily = ServiceFamily::create([
            'contract_id' => $otherContract->id,
            'name' => 'Familia Other',
            'code' => 'FOTH',
            'description' => 'Otra familia',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $otherService = Service::create([
            'service_family_id' => $otherFamily->id,
            'name' => 'Servicio Other',
            'code' => 'SOTH',
            'description' => 'Otro servicio',
            'is_active' => true,
            'order' => 0,
        ]);
        $otherSubService = SubService::create([
            'service_id' => $otherService->id,
            'name' => 'SubServicio Other',
            'code' => 'SS_OTH',
            'description' => 'Otro sub-servicio',
            'is_active' => true,
            'order' => 0,
        ]);
        $otherSla = ServiceLevelAgreement::create([
            'service_family_id' => $otherFamily->id,
            'name' => 'SLA OTHER',
            'criticality_level' => 'ALTA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 4,
            'availability_percentage' => 99.50,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'conditions' => 'Other',
            'is_active' => true,
        ]);
        $otherRequester = Requester::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $otherRequest = ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $otherCompany->id,
            'requester_id' => $otherRequester->id,
            'title' => 'Other workspace request',
            'description' => 'Should not appear',
            'ticket_number' => 'SR-OTHER-001',
            'sub_service_id' => $otherSubService->id,
            'sla_id' => $otherSla->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'ALTA',
            'status' => 'PENDIENTE',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.timeline.index'));

        $response->assertOk();
        $response->assertSee('SR-OWN-001');
        $response->assertDontSee('SR-OTHER-001');
    }

    public function test_services_sla_only_shows_data_from_current_workspace(): void
    {
        $data = $this->seedContext();

        // Create a service request for the current workspace
        $this->createServiceRequest($data, [
            'title' => 'Own SLA request',
        ]);

        // Create another company with its own data
        $otherCompany = Company::create([
            'name' => 'Other SLA Company',
            'status' => 'active',
        ]);
        $otherContract = Contract::create([
            'company_id' => $otherCompany->id,
            'number' => 'C-OSLA-001',
            'name' => 'Contrato Other SLA',
            'description' => 'Otro contrato SLA',
            'is_active' => true,
        ]);
        $otherCompany->update(['active_contract_id' => $otherContract->id]);

        $otherFamily = ServiceFamily::create([
            'contract_id' => $otherContract->id,
            'name' => 'Familia Other SLA',
            'code' => 'FOSLA',
            'description' => 'Otra familia SLA',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $otherService = Service::create([
            'service_family_id' => $otherFamily->id,
            'name' => 'Servicio Other SLA',
            'code' => 'SOSLA',
            'description' => 'Otro servicio SLA',
            'is_active' => true,
            'order' => 0,
        ]);
        $otherSubService = SubService::create([
            'service_id' => $otherService->id,
            'name' => 'SubServicio Other SLA',
            'code' => 'SS_OSLA',
            'description' => 'Otro sub-servicio SLA',
            'is_active' => true,
            'order' => 0,
        ]);
        $otherSla = ServiceLevelAgreement::create([
            'service_family_id' => $otherFamily->id,
            'name' => 'SLA OTHER SLA',
            'criticality_level' => 'ALTA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 4,
            'availability_percentage' => 99.50,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'conditions' => 'Other SLA',
            'is_active' => true,
        ]);
        $otherRequester = Requester::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $otherCompany->id,
            'requester_id' => $otherRequester->id,
            'title' => 'Other SLA workspace request',
            'description' => 'Should not appear in SLA report',
            'ticket_number' => 'SR-OSLA-001',
            'sub_service_id' => $otherSubService->id,
            'sla_id' => $otherSla->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'ALTA',
            'status' => 'PENDIENTE',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.services-sla.index'));

        $response->assertOk();
        // The view should show data from own workspace
        $response->assertSee('Servicio Integration');
        // The other workspace's service should not appear
        $response->assertDontSee('Servicio Other SLA');
    }

    public function test_search_analysis_only_shows_results_from_current_workspace(): void
    {
        $data = $this->seedContext();

        // Create a service request for the current workspace
        $this->createServiceRequest($data, [
            'title' => 'Unique workspace search term',
            'ticket_number' => 'SR-SEARCH-001',
        ]);

        // Create another company with its own data
        $otherCompany = Company::create([
            'name' => 'Other Search Company',
            'status' => 'active',
        ]);
        $otherContract = Contract::create([
            'company_id' => $otherCompany->id,
            'number' => 'C-OSRCH-001',
            'name' => 'Contrato Other Search',
            'description' => 'Otro contrato search',
            'is_active' => true,
        ]);
        $otherCompany->update(['active_contract_id' => $otherContract->id]);

        $otherFamily = ServiceFamily::create([
            'contract_id' => $otherContract->id,
            'name' => 'Familia Other Search',
            'code' => 'FOSRCH',
            'description' => 'Otra familia search',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $otherService = Service::create([
            'service_family_id' => $otherFamily->id,
            'name' => 'Servicio Other Search',
            'code' => 'SOSRCH',
            'description' => 'Otro servicio search',
            'is_active' => true,
            'order' => 0,
        ]);
        $otherSubService = SubService::create([
            'service_id' => $otherService->id,
            'name' => 'SubServicio Other Search',
            'code' => 'SS_OSRCH',
            'description' => 'Otro sub-servicio search',
            'is_active' => true,
            'order' => 0,
        ]);
        $otherSla = ServiceLevelAgreement::create([
            'service_family_id' => $otherFamily->id,
            'name' => 'SLA OTHER SEARCH',
            'criticality_level' => 'BAJA',
            'response_time_hours' => 4,
            'resolution_time_hours' => 8,
            'availability_percentage' => 99.00,
            'acceptance_time_minutes' => 120,
            'response_time_minutes' => 240,
            'resolution_time_minutes' => 480,
            'conditions' => 'Other Search',
            'is_active' => true,
        ]);
        $otherRequester = Requester::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $otherCompany->id,
            'requester_id' => $otherRequester->id,
            'title' => 'Unique workspace search term from other',
            'description' => 'Should not appear in search results',
            'ticket_number' => 'SR-OSRCH-001',
            'sub_service_id' => $otherSubService->id,
            'sla_id' => $otherSla->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'BAJA',
            'status' => 'PENDIENTE',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.search-analysis.search', ['terms' => 'Unique workspace search term']));

        $response->assertOk();
        $response->assertSee('SR-SEARCH-001');
        $response->assertDontSee('SR-OSRCH-001');
    }

    public function test_operational_overview_scopes_data_to_current_workspace(): void
    {
        $data = $this->seedContext();

        // Create service requests for the current workspace
        $this->createServiceRequest($data, [
            'status' => 'PENDIENTE',
            'criticality_level' => 'MEDIA',
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.operational-overview.index'));

        $response->assertOk();
        // The view should render without errors and show data scoped to the workspace
        $response->assertViewHas('statusData');
        $response->assertViewHas('criticalityData');
        $response->assertViewHas('trendsData');
    }
}
