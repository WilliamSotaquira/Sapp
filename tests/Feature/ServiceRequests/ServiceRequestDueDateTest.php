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

class ServiceRequestDueDateTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Empresa Vencimientos',
            'status' => 'active',
        ]);
        $user->companies()->syncWithoutDetaching([$company->id]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-VEN-001',
            'name' => 'Contrato vencimientos',
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
            'name' => 'Solicitante Vencimientos',
            'email' => 'vencimientos@example.com',
        ]);

        return compact('user', 'company', 'family', 'service', 'subService', 'sla', 'requester');
    }

    public function test_store_persists_optional_due_date(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('service-requests.store'), [
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud con vencimiento',
                'description' => 'Solicitud de prueba con vencimiento propio.',
                'sub_service_id' => $data['subService']->id,
                'criticality_level' => 'MEDIA',
                'service_id' => $data['service']->id,
                'family_id' => $data['family']->id,
                'sla_id' => $data['sla']->id,
                'requested_by' => $data['user']->id,
                'entry_channel' => 'email_corporativo',
                'due_date' => '2026-05-05',
                'web_routes' => ['ruta-prueba'],
                'is_reportable' => true,
                'tasks_template' => 'none',
                'tasks' => [
                    [
                        'title' => 'Atender vencimiento',
                        'priority' => 'high',
                        'type' => 'regular',
                        'estimated_minutes' => 30,
                    ],
                ],
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $created = ServiceRequest::withoutGlobalScopes()
            ->where('title', 'Solicitud con vencimiento')
            ->firstOrFail();

        $this->assertSame('2026-05-05', $created->due_date->toDateString());
    }

    public function test_update_can_clear_due_date(): void
    {
        $data = $this->seedContext();

        $serviceRequest = $this->createServiceRequest($data, [
            'title' => 'Solicitud para limpiar vencimiento',
            'due_date' => '2026-05-05',
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->put(route('service-requests.update', $serviceRequest), [
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud para limpiar vencimiento',
                'description' => 'Solicitud de prueba para limpiar vencimiento.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'criticality_level' => 'MEDIA',
                'entry_channel' => 'email_corporativo',
                'is_reportable' => true,
                'due_date' => '',
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $this->assertNull($serviceRequest->fresh()->due_date);
    }

    public function test_due_status_filter_only_returns_open_overdue_requests(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-05-10 09:00:00'));

        try {
            $this->createServiceRequest($data, [
                'title' => 'Solicitud vencida abierta',
                'due_date' => '2026-05-09',
                'status' => 'PENDIENTE',
            ]);
            $this->createServiceRequest($data, [
                'title' => 'Solicitud por vencer',
                'due_date' => '2026-05-11',
                'status' => 'PENDIENTE',
            ]);
            $closedRequest = $this->createServiceRequest($data, [
                'title' => 'Solicitud vencida cerrada',
                'due_date' => '2026-05-08',
            ]);
            $closedRequest->forceFill([
                'status' => 'CERRADA',
                'closed_at' => '2026-05-09 10:00:00',
            ])->saveQuietly();
            $this->createServiceRequest($data, [
                'title' => 'Solicitud sin vencimiento',
                'due_date' => null,
                'status' => 'PENDIENTE',
            ]);

            $directTitles = ServiceRequest::withoutGlobalScopes()
                ->whereIn('status', ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'REABIERTO'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->pluck('title')
                ->all();
            $this->assertSame(['Solicitud vencida abierta'], $directTitles);

            session(['current_company_id' => $data['company']->id]);
            $serviceTitles = app(\App\Services\ServiceRequestService::class)
                ->getFilteredServiceRequests([
                    'company_id' => $data['company']->id,
                    'due_status' => 'overdue',
                ])
                ->pluck('title')
                ->all();
            $this->assertSame(['Solicitud vencida abierta'], $serviceTitles);

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->get(route('service-requests.index', ['due_status' => 'overdue']));
        } finally {
            Carbon::setTestNow();
        }

        $response->assertOk();
        $titles = $response->viewData('serviceRequests')->pluck('title')->all();
        $this->assertSame(['Solicitud vencida abierta'], $titles);
    }

    private function createServiceRequest(array $data, array $overrides = []): ServiceRequest
    {
        return ServiceRequest::withoutGlobalScopes()->create(array_merge([
            'company_id' => $data['company']->id,
            'requester_id' => $data['requester']->id,
            'title' => 'Solicitud base',
            'description' => 'Solicitud de prueba para vencimientos.',
            'sub_service_id' => $data['subService']->id,
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'PENDIENTE',
            'created_at' => '2026-05-01 08:00:00',
        ], $overrides));
    }
}
