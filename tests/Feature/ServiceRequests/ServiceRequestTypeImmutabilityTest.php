<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\Company;
use App\Models\Contract;
use App\Models\RequestType;
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

class ServiceRequestTypeImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();
        $companyId = $user->companies()->value('companies.id');
        $company = Company::findOrFail($companyId);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-IMM-001',
            'name' => 'Contrato inmutabilidad',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Inmutabilidad',
            'code' => 'FIM',
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Inmutabilidad',
            'code' => 'SIM',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Sub Inmutabilidad',
            'code' => 'SBI',
            'description' => 'Subservicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $service->id,
            'sub_service_id' => $subService->id,
            'name' => 'Sub Inmutabilidad',
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
            'name' => 'Solicitante Inmutabilidad',
            'email' => 'inmutabilidad@example.com',
        ]);

        $typeGeneral = RequestType::where('slug', 'general')->first()
            ?? RequestType::create(['slug' => 'general', 'name' => 'General', 'is_active' => true]);

        $typeReunion = RequestType::where('slug', 'reunion')->first()
            ?? RequestType::create(['slug' => 'reunion', 'name' => 'Reunión', 'is_active' => true]);

        return compact('user', 'company', 'subService', 'sla', 'requester', 'typeGeneral', 'typeReunion');
    }

    public function test_cannot_change_request_type_after_creation(): void
    {
        $data = $this->seedContext();

        $serviceRequest = ServiceRequest::withoutEvents(function () use ($data) {
            return ServiceRequest::create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'request_type_id' => $data['typeGeneral']->id,
                'ticket_number' => 'IMM-001-TEST',
                'title' => 'Solicitud con tipo inmutable',
                'description' => 'Prueba de inmutabilidad del tipo.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
            ]);
        });

        // Re-fetch from DB so wasRecentlyCreated is false
        $serviceRequest = ServiceRequest::withoutGlobalScopes()->find($serviceRequest->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se puede cambiar el tipo de una solicitud después de su creación.');

        $serviceRequest->request_type_id = $data['typeReunion']->id;
        $serviceRequest->save();
    }

    public function test_can_assign_type_to_legacy_request_with_null_type(): void
    {
        $data = $this->seedContext();

        $serviceRequest = ServiceRequest::withoutEvents(function () use ($data) {
            return ServiceRequest::create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'request_type_id' => null,
                'ticket_number' => 'IMM-002-TEST',
                'title' => 'Solicitud legacy sin tipo',
                'description' => 'Prueba de asignación de tipo a solicitud legacy.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
            ]);
        });

        // Re-fetch from DB so wasRecentlyCreated is false (simulates real-world usage)
        $serviceRequest = ServiceRequest::withoutGlobalScopes()->find($serviceRequest->id);

        // Assigning type to a legacy request (null → value) should succeed
        $serviceRequest->request_type_id = $data['typeGeneral']->id;
        $serviceRequest->save();

        $this->assertEquals($data['typeGeneral']->id, $serviceRequest->fresh()->request_type_id);
    }

    public function test_can_set_type_on_initial_creation(): void
    {
        $data = $this->seedContext();

        // Setting type during creation should work without issues
        $serviceRequest = ServiceRequest::withoutEvents(function () use ($data) {
            return ServiceRequest::create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'request_type_id' => $data['typeReunion']->id,
                'ticket_number' => 'IMM-003-TEST',
                'title' => 'Solicitud creada con tipo reunión',
                'description' => 'Prueba de creación con tipo.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
            ]);
        });

        $this->assertEquals($data['typeReunion']->id, $serviceRequest->request_type_id);
    }

    public function test_cannot_change_type_to_null_after_creation(): void
    {
        $data = $this->seedContext();

        $serviceRequest = ServiceRequest::withoutEvents(function () use ($data) {
            return ServiceRequest::create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'request_type_id' => $data['typeGeneral']->id,
                'ticket_number' => 'IMM-004-TEST',
                'title' => 'Solicitud tipo no puede ser null',
                'description' => 'Prueba de cambio a null.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
            ]);
        });

        // Re-fetch from DB so wasRecentlyCreated is false
        $serviceRequest = ServiceRequest::withoutGlobalScopes()->find($serviceRequest->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se puede cambiar el tipo de una solicitud después de su creación.');

        $serviceRequest->request_type_id = null;
        $serviceRequest->save();
    }
}
