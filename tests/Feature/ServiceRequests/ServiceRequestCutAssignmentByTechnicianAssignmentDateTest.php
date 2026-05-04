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
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Models\User;
use App\Services\ServiceRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestCutAssignmentByTechnicianAssignmentDateTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();
        $alternateTechnician = User::factory()->create();

        $company = Company::create([
            'name' => 'Empresa Cortes',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-CREA-001',
            'name' => 'Contrato cortes por asignacion',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        $marchCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte marzo',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'created_by' => $user->id,
        ]);

        $aprilCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte abril',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'created_by' => $user->id,
        ]);

        $mayCut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte mayo',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'created_by' => $user->id,
        ]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Web',
            'code' => 'FWEB',
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Web',
            'code' => 'SWEB',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Publicacion',
            'code' => 'PUB',
            'description' => 'Subservicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $service->id,
            'sub_service_id' => $subService->id,
            'name' => 'Publicacion',
            'description' => 'Relacion de prueba',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA MEDIA',
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

        if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
            $sla->forceFill(['sub_service_id' => $subService->id])->save();
        }

        $requester = Requester::factory()->create([
            'company_id' => $company->id,
            'name' => 'Solicitante Cortes',
            'email' => 'cortes@example.com',
        ]);

        return compact(
            'user',
            'alternateTechnician',
            'company',
            'contract',
            'family',
            'service',
            'subService',
            'serviceSubservice',
            'sla',
            'requester',
            'marchCut',
            'aprilCut',
            'mayCut'
        );
    }

    public function test_cut_is_assigned_only_after_acceptance_using_technician_assignment_date(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:15:00'));

        try {
            $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud creada en marzo y asignada en abril',
                'description' => 'Debe tomar el corte de la asignacion tecnica.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'assigned_to' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
                'created_at' => '2026-03-20 14:45:00',
            ]);

            $this->assertSame('2026-04-10 09:15:00', $serviceRequest->technician_assigned_at?->format('Y-m-d H:i:s'));
            $this->assertSame([], $serviceRequest->cuts()->pluck('cuts.id')->all());

            $result = $this->actingAs($data['user'])
                ->app->make(ServiceRequestWorkflowService::class)
                ->acceptRequest($serviceRequest);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertTrue($result['success']);
        $this->assertSame([$data['aprilCut']->id], $serviceRequest->fresh()->cuts()->pluck('cuts.id')->all());
    }

    public function test_store_rejects_future_manual_created_at(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:15:00'));

        try {
            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->from(route('service-requests.create'))
                ->post(route('service-requests.store'), [
                    'company_id' => $data['company']->id,
                    'requester_id' => $data['requester']->id,
                    'title' => 'Solicitud con fecha futura',
                    'description' => 'Solicitud de prueba con fecha futura.',
                    'sub_service_id' => $data['subService']->id,
                    'criticality_level' => 'MEDIA',
                    'service_id' => $data['service']->id,
                    'family_id' => $data['family']->id,
                    'sla_id' => $data['sla']->id,
                    'requested_by' => $data['user']->id,
                    'entry_channel' => 'email_corporativo',
                    'created_at' => '2026-04-11T10:00',
                    'web_routes' => ['ruta-prueba'],
                    'is_reportable' => true,
                    'tasks_template' => 'none',
                    'tasks' => [
                        [
                            'title' => 'Validar fecha futura',
                            'priority' => 'medium',
                            'type' => 'regular',
                            'estimated_minutes' => 30,
                        ],
                    ],
                ]);
        } finally {
            Carbon::setTestNow();
        }

        $response->assertRedirect(route('service-requests.create'));
        $response->assertSessionHasErrors('created_at');

        $this->assertDatabaseMissing('service_requests', [
            'title' => 'Solicitud con fecha futura',
        ]);
    }

    public function test_update_created_at_does_not_recalculate_cut_association(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:15:00'));

        try {
            $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud movible',
                'description' => 'Solicitud para validar que created_at no mueve el corte.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'assigned_to' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'ACEPTADA',
                'created_at' => '2026-03-20 10:00:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame([$data['aprilCut']->id], $serviceRequest->cuts()->pluck('cuts.id')->all());

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->put(route('service-requests.update', $serviceRequest), [
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud movible',
                'description' => 'Solicitud para validar que created_at no mueve el corte.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'criticality_level' => 'MEDIA',
                'entry_channel' => 'email_corporativo',
                'is_reportable' => true,
                'created_at' => '2026-03-05T08:30',
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $fresh = $serviceRequest->fresh();

        $this->assertSame('2026-03-05 08:30:00', $fresh->created_at->format('Y-m-d H:i:s'));
        $this->assertSame([$data['aprilCut']->id], $fresh->cuts()->pluck('cuts.id')->all());
    }

    public function test_reassigning_an_accepted_request_recalculates_cut_using_new_assignment_date(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:15:00'));

        try {
            $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud reasignable',
                'description' => 'Solicitud para validar reasignacion de corte.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'assigned_to' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'ACEPTADA',
                'created_at' => '2026-03-20 10:00:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame([$data['aprilCut']->id], $serviceRequest->cuts()->pluck('cuts.id')->all());

        Carbon::setTestNow(Carbon::parse('2026-05-03 11:00:00'));

        try {
            $serviceRequest->update([
                'assigned_to' => $data['alternateTechnician']->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $fresh = $serviceRequest->fresh();

        $this->assertSame('2026-05-03 11:00:00', $fresh->technician_assigned_at?->format('Y-m-d H:i:s'));
        $this->assertSame([$data['mayCut']->id], $fresh->cuts()->pluck('cuts.id')->all());
    }
}
