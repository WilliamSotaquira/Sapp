<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();
        $companyId = $user->companies()->value('companies.id');
        $company = Company::findOrFail($companyId);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-DEL-001',
            'name' => 'Contrato borrado de prueba',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

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

        $slaAttributes = [
            'name' => 'SLA MEDIA',
            'description' => 'SLA de prueba',
            'service_family_id' => $family->id,
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 4,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'conditions' => null,
            'is_active' => true,
        ];

        if (Schema::hasColumn('service_level_agreements', 'service_subservice_id')) {
            $slaAttributes['service_subservice_id'] = $serviceSubservice->id;
        }

        if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
            $slaAttributes['sub_service_id'] = $subService->id;
        }

        $sla = ServiceLevelAgreement::create($slaAttributes);

        $requester = Requester::factory()->create([
            'company_id' => $company->id,
            'name' => 'Solicitante Borrado',
            'email' => 'borrado@example.com',
        ]);

        return compact('user', 'company', 'service', 'subService', 'sla', 'requester');
    }

    public function test_closed_service_request_can_be_deleted(): void
    {
        $data = $this->seedContext();

        $serviceRequest = ServiceRequest::withoutEvents(function () use ($data) {
            return ServiceRequest::create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'ticket_number' => 'DEL-645-TEST',
                'title' => 'Solicitud cerrada para eliminar',
                'description' => 'Registro de prueba para validar el borrado.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'CERRADA',
                'closed_at' => now(),
                'is_reportable' => true,
            ]);
        });

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->from(route('service-requests.show', $serviceRequest))
            ->delete(route('service-requests.destroy', $serviceRequest));

        $response->assertRedirect(route('service-requests.index'));
        $response->assertSessionHas('success', 'Solicitud de servicio eliminada exitosamente.');

        $this->assertSoftDeleted('service_requests', [
            'id' => $serviceRequest->id,
        ]);
    }

    public function test_in_progress_service_request_can_be_deleted(): void
    {
        $data = $this->seedContext();

        $serviceRequest = ServiceRequest::withoutEvents(function () use ($data) {
            return ServiceRequest::create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'ticket_number' => 'DEL-640-TEST',
                'title' => 'Solicitud en proceso para eliminar',
                'description' => 'Registro de prueba para validar el borrado en proceso.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'assigned_to' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'EN_PROCESO',
                'accepted_at' => now()->subHour(),
                'responded_at' => now()->subMinutes(30),
                'resolved_at' => null,
                'closed_at' => null,
                'paused_at' => now()->subMinutes(20),
                'resumed_at' => now()->subMinutes(10),
                'is_reportable' => true,
            ]);
        });

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->from(route('service-requests.show', $serviceRequest))
            ->delete(route('service-requests.destroy', $serviceRequest));

        $response->assertRedirect(route('service-requests.index'));
        $response->assertSessionHas('success', 'Solicitud de servicio eliminada exitosamente.');

        $this->assertSoftDeleted('service_requests', [
            'id' => $serviceRequest->id,
        ]);
    }
}
