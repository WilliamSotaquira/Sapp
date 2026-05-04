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

class CutValidationTest extends TestCase
{
    use RefreshDatabase;

    private function seedCutContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Movilidad Test',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-CUT-001',
            'name' => 'Contrato cortes',
            'description' => 'Contrato de prueba para cortes',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        $marchCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Marzo 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'created_by' => $user->id,
        ]);

        $aprilCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Abril 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'created_by' => $user->id,
        ]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Web',
            'code' => 'FWEB',
            'description' => 'Familia web',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Web',
            'code' => 'SWEB',
            'description' => 'Servicio web',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Publicacion',
            'code' => 'PUB_WEB',
            'description' => 'Subservicio web',
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
            'email' => 'solicitante@example.com',
        ]);

        return compact('user', 'company', 'contract', 'marchCut', 'aprilCut', 'subService', 'sla', 'requester');
    }

    public function test_store_cut_rejects_overlapping_range_for_same_contract(): void
    {
        $data = $this->seedCutContext();

        $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('reports.cuts.store'), [
                'name' => 'Corte solapado',
                'start_date' => '2026-03-20',
                'end_date' => '2026-04-10',
                'notes' => 'No deberia guardar',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cuts', 2);
        $this->assertDatabaseMissing('cuts', [
            'name' => 'Corte solapado',
        ]);
    }

    public function test_update_cut_endpoint_rejects_cut_outside_accepted_technician_assignment_date(): void
    {
        $data = $this->seedCutContext();

        $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $data['company']->id,
            'requester_id' => $data['requester']->id,
            'title' => 'Solicitud marzo',
            'description' => 'Debe permanecer en marzo.',
            'sub_service_id' => $data['subService']->id,
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'assigned_to' => $data['user']->id,
            'technician_assigned_at' => '2026-03-15 10:00:00',
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'ACEPTADA',
            'created_at' => '2026-04-15 10:00:00',
        ]);

        $this->actingAs($data['user'])
            ->postJson(route('service-requests.update-cut', $serviceRequest), [
                'cut_id' => $data['aprilCut']->id,
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'El corte seleccionado no corresponde a la fecha de asignación aceptada del técnico.',
            ]);

        $this->assertSame([$data['marchCut']->id], $serviceRequest->fresh()->cuts()->pluck('cuts.id')->all());
    }
}
