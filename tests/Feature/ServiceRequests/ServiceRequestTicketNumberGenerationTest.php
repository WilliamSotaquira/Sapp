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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestTicketNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_skips_soft_deleted_ticket_numbers_when_generating_a_new_ticket(): void
    {
        $data = $this->seedContext('Empresa Ticket Soft Delete', 'ticket-soft-delete@example.com');

        Carbon::setTestNow(Carbon::parse('2026-05-15 11:29:55'));

        try {
            $deletedRequest = ServiceRequest::withoutGlobalScopes()->create([
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud eliminada',
                'description' => 'Solicitud previa eliminada para probar consecutivos.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
                'created_at' => '2026-05-13 16:25:00',
                'ticket_number' => 'WEB-ED-M-260515-001',
            ]);
            $deletedRequest->delete();

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.store'), $this->storePayload(
                    $data,
                    'Solicitud nueva con ticket consecutivo',
                    'Debe usar el siguiente consecutivo aunque exista un ticket eliminado.'
                ));

            $response->assertRedirect()->assertSessionHas('success');

            $created = ServiceRequest::withoutGlobalScopes()
                ->where('title', 'Solicitud nueva con ticket consecutivo')
                ->firstOrFail();

            $this->assertSame('WEB-ED-M-260515-002', $created->ticket_number);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_store_generates_globally_unique_ticket_numbers_across_workspaces(): void
    {
        $firstWorkspace = $this->seedContext('Empresa Ticket Uno', 'ticket-uno@example.com');
        $secondWorkspace = $this->seedContext('Empresa Ticket Dos', 'ticket-dos@example.com');

        Carbon::setTestNow(Carbon::parse('2026-05-15 11:29:55'));

        try {
            ServiceRequest::withoutGlobalScopes()->create([
                'company_id' => $firstWorkspace['company']->id,
                'requester_id' => $firstWorkspace['requester']->id,
                'title' => 'Solicitud existente en otra entidad',
                'description' => 'Solicitud previa para probar unicidad global del ticket.',
                'sub_service_id' => $firstWorkspace['subService']->id,
                'sla_id' => $firstWorkspace['sla']->id,
                'requested_by' => $firstWorkspace['user']->id,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'PENDIENTE',
                'created_at' => '2026-05-15 09:00:00',
                'ticket_number' => 'WEB-ED-M-260515-001',
            ]);

            $response = $this->actingAs($secondWorkspace['user'])
                ->withSession(['current_company_id' => $secondWorkspace['company']->id])
                ->post(route('service-requests.store'), $this->storePayload(
                    $secondWorkspace,
                    'Solicitud en segunda entidad',
                    'Debe generar un ticket distinto aunque el prefijo coincida en otra entidad.'
                ));

            $response->assertRedirect()->assertSessionHas('success');

            $created = ServiceRequest::withoutGlobalScopes()
                ->where('company_id', $secondWorkspace['company']->id)
                ->where('title', 'Solicitud en segunda entidad')
                ->firstOrFail();

            $this->assertSame('WEB-ED-M-260515-002', $created->ticket_number);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function seedContext(string $companyName, string $requesterEmail): array
    {
        $user = User::factory()->create();
        $companyCodeSuffix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $companyName), -2));

        $company = Company::create([
            'name' => $companyName,
            'status' => 'active',
        ]);
        $user->companies()->syncWithoutDetaching([$company->id]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-TICKET-' . $company->id,
            'name' => 'Contrato ' . $companyName,
            'description' => 'Contrato de prueba para tickets',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Familia Web',
            'code' => 'WEB' . $companyCodeSuffix,
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Edicion',
            'code' => 'ED' . $companyCodeSuffix,
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Edicion Web',
            'code' => 'ED' . $companyCodeSuffix . '_WEB',
            'description' => 'Subservicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $service->id,
            'sub_service_id' => $subService->id,
            'name' => 'Edicion Web',
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
            'name' => 'Solicitante ' . $companyName,
            'email' => $requesterEmail,
        ]);

        return compact('user', 'company', 'family', 'service', 'subService', 'sla', 'requester');
    }

    private function storePayload(array $data, string $title, string $description): array
    {
        return [
            'company_id' => $data['company']->id,
            'requester_id' => $data['requester']->id,
            'title' => $title,
            'description' => $description,
            'sub_service_id' => $data['subService']->id,
            'criticality_level' => 'MEDIA',
            'service_id' => $data['service']->id,
            'family_id' => $data['family']->id,
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => 'email_corporativo',
            'web_routes' => ['ruta-prueba'],
            'is_reportable' => true,
            'tasks_template' => 'none',
            'tasks' => [
                [
                    'title' => 'Atender solicitud',
                    'priority' => 'medium',
                    'type' => 'regular',
                    'estimated_minutes' => 30,
                ],
            ],
        ];
    }
}
