<?php

namespace Tests\Feature\Reports;

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

class TimeRangeReportCutSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Test Company',
            'status' => 'active',
        ]);

        // Associate user with company (required for EnsureWorkspaceSelected middleware)
        $company->users()->attach($user->id);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-TR-001',
            'name' => 'Contrato Time Range',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        // Verify the update persisted
        $company->refresh();

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Test',
            'code' => 'FTEST',
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Test',
            'code' => 'STEST',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'SubServicio Test',
            'code' => 'SS_TEST',
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
            'name' => 'Solicitante Test',
            'email' => 'solicitante@test.com',
        ]);

        return compact('user', 'company', 'contract', 'family', 'service', 'subService', 'sla', 'requester');
    }

    public function test_index_loads_cuts_from_active_contract(): void
    {
        $data = $this->seedContext();

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Enero',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'created_by' => $data['user']->id,
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.time-range.index'));

        $response->assertStatus(200);
        $response->assertViewHas('cuts');
        $cuts = $response->viewData('cuts');
        $this->assertTrue($cuts->contains('id', $cut->id));
    }

    public function test_index_returns_empty_cuts_when_no_cuts_exist(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.time-range.index'));

        $response->assertStatus(200);
        $response->assertViewHas('cuts');
        $cuts = $response->viewData('cuts');
        $this->assertCount(0, $cuts);
    }

    public function test_index_orders_cuts_by_start_date_desc(): void
    {
        $data = $this->seedContext();

        $olderCut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Enero',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'created_by' => $data['user']->id,
        ]);

        $newerCut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Marzo',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'created_by' => $data['user']->id,
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.time-range.index'));

        $response->assertStatus(200);
        $cuts = $response->viewData('cuts');
        $this->assertCount(2, $cuts);
        $this->assertEquals($newerCut->id, $cuts->first()->id);
        $this->assertEquals($olderCut->id, $cuts->last()->id);
    }

    public function test_generate_with_cut_id_uses_cut_relationship(): void
    {
        $data = $this->seedContext();

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Febrero',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'created_by' => $data['user']->id,
        ]);

        // Create a service request - the auto-sync in the model will associate it with the cut
        // because technician_assigned_at is within the cut's date range
        $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id]);

        session(['current_company_id' => $data['company']->id]);

        $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $data['company']->id,
            'requester_id' => $data['requester']->id,
            'title' => 'Solicitud en corte',
            'description' => 'Solicitud asociada al corte.',
            'sub_service_id' => $data['subService']->id,
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
            'technician_assigned_at' => '2026-02-15 10:00:00',
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'ACEPTADA',
            'created_at' => '2026-02-15 10:00:00',
        ]);

        // Ensure the cut has the service request associated
        // (auto-sync should have done this, but let's make sure)
        if ($cut->serviceRequests()->count() === 0) {
            $cut->serviceRequests()->syncWithoutDetaching([$serviceRequest->id]);
        }

        // Generate report using cut_id
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => [$data['family']->id],
            ]);

        // Should not redirect back with error (successful generation returns a download)
        $response->assertStatus(200);
    }

    public function test_generate_with_cut_id_rejects_cut_with_no_requests(): void
    {
        $data = $this->seedContext();

        // Create a cut with dates far in the future so no service requests will be auto-synced
        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Vacío',
            'start_date' => '2030-05-01',
            'end_date' => '2030-05-31',
            'created_by' => $data['user']->id,
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => [$data['family']->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'El corte seleccionado no tiene solicitudes de servicio asociadas. No se puede generar el reporte.');
    }

    public function test_generate_without_cut_id_requires_manual_dates(): void
    {
        $data = $this->seedContext();

        // Without cut_id, start_date and end_date are required
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'format' => 'pdf',
                'families' => [$data['family']->id],
            ]);

        $response->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_generate_with_cut_id_does_not_require_dates(): void
    {
        $data = $this->seedContext();

        $cut = Cut::create([
            'contract_id' => $data['contract']->id,
            'name' => 'Corte Test',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'created_by' => $data['user']->id,
        ]);

        session(['current_company_id' => $data['company']->id]);

        // Create a service request within the cut's date range
        $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $data['company']->id,
            'requester_id' => $data['requester']->id,
            'title' => 'Solicitud junio',
            'description' => 'Solicitud de junio.',
            'sub_service_id' => $data['subService']->id,
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
            'technician_assigned_at' => '2026-06-15 10:00:00',
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'ACEPTADA',
            'created_at' => '2026-06-15 10:00:00',
        ]);

        // Ensure the cut has the service request associated
        if ($cut->serviceRequests()->count() === 0) {
            $cut->serviceRequests()->syncWithoutDetaching([$serviceRequest->id]);
        }

        // When cut_id is present, start_date/end_date are not required
        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.time-range.generate'), [
                'cut_id' => $cut->id,
                'format' => 'pdf',
                'families' => [$data['family']->id],
            ]);

        // Should succeed (returns PDF download) - no validation errors for dates
        $response->assertSessionDoesntHaveErrors(['start_date', 'end_date']);
    }
}
