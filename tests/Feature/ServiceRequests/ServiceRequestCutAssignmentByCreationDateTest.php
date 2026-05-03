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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestCutAssignmentByCreationDateTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Empresa Cortes',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-CREA-001',
            'name' => 'Contrato cortes por fecha',
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
            'company',
            'contract',
            'family',
            'service',
            'subService',
            'serviceSubservice',
            'sla',
            'requester',
            'marchCut',
            'aprilCut'
        );
    }

    public function test_store_assigns_cut_by_created_at_and_ignores_submitted_cut_id(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:15:00'));

        try {
            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.store'), [
                    'company_id' => $data['company']->id,
                    'requester_id' => $data['requester']->id,
                    'title' => 'Solicitud creada en abril',
                    'description' => 'Solicitud de prueba para asociacion automatica.',
                    'sub_service_id' => $data['subService']->id,
                    'criticality_level' => 'MEDIA',
                    'service_id' => $data['service']->id,
                    'family_id' => $data['family']->id,
                    'sla_id' => $data['sla']->id,
                    'requested_by' => $data['user']->id,
                    'entry_channel' => 'email_corporativo',
                    'web_routes' => ['ruta-prueba'],
                    'is_reportable' => true,
                    'cut_id' => $data['marchCut']->id,
                    'tasks_template' => 'none',
                    'tasks' => [
                        [
                            'title' => 'Publicar contenido de prueba',
                            'priority' => 'medium',
                            'type' => 'regular',
                            'estimated_minutes' => 30,
                        ],
                    ],
                ]);
        } finally {
            Carbon::setTestNow();
        }

        $response->assertRedirect()->assertSessionHas('success');

        $created = ServiceRequest::withoutGlobalScopes()
            ->where('title', 'Solicitud creada en abril')
            ->firstOrFail();

        $this->assertSame([$data['aprilCut']->id], $created->cuts()->pluck('cuts.id')->all());
    }

    public function test_store_respects_manual_created_at_and_assigns_the_matching_cut(): void
    {
        $data = $this->seedContext();
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:15:00'));

        try {
            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.store'), [
                    'company_id' => $data['company']->id,
                    'requester_id' => $data['requester']->id,
                    'title' => 'Solicitud con fecha manual',
                    'description' => 'Solicitud de prueba con fecha ingresada manualmente.',
                    'sub_service_id' => $data['subService']->id,
                    'criticality_level' => 'MEDIA',
                    'service_id' => $data['service']->id,
                    'family_id' => $data['family']->id,
                    'sla_id' => $data['sla']->id,
                    'requested_by' => $data['user']->id,
                    'entry_channel' => 'email_corporativo',
                    'created_at' => '2026-03-20T14:45',
                    'web_routes' => ['ruta-prueba'],
                    'is_reportable' => true,
                    'cut_id' => $data['aprilCut']->id,
                    'tasks_template' => 'none',
                    'tasks' => [
                        [
                            'title' => 'Publicar contenido con fecha manual',
                            'priority' => 'medium',
                            'type' => 'regular',
                            'estimated_minutes' => 30,
                        ],
                    ],
                ]);
        } finally {
            Carbon::setTestNow();
        }

        $response->assertRedirect()->assertSessionHas('success');

        $created = ServiceRequest::withoutGlobalScopes()
            ->where('title', 'Solicitud con fecha manual')
            ->firstOrFail();

        $this->assertSame('2026-03-20 14:45:00', $created->created_at->format('Y-m-d H:i:s'));
        $this->assertSame([$data['marchCut']->id], $created->cuts()->pluck('cuts.id')->all());
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

    public function test_update_created_at_recalculates_cut_association(): void
    {
        $data = $this->seedContext();

        $serviceRequest = ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $data['company']->id,
            'requester_id' => $data['requester']->id,
            'title' => 'Solicitud movible',
            'description' => 'Solicitud para mover entre cortes.',
            'sub_service_id' => $data['subService']->id,
            'sla_id' => $data['sla']->id,
            'requested_by' => $data['user']->id,
            'entry_channel' => 'email_corporativo',
            'criticality_level' => 'MEDIA',
            'status' => 'PENDIENTE',
            'created_at' => '2026-04-08 10:00:00',
        ]);

        $serviceRequest->cuts()->attach($data['aprilCut']->id);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->put(route('service-requests.update', $serviceRequest), [
                'company_id' => $data['company']->id,
                'requester_id' => $data['requester']->id,
                'title' => 'Solicitud movible',
                'description' => 'Solicitud para mover entre cortes.',
                'sub_service_id' => $data['subService']->id,
                'sla_id' => $data['sla']->id,
                'criticality_level' => 'MEDIA',
                'entry_channel' => 'email_corporativo',
                'is_reportable' => true,
                'created_at' => '2026-03-15T08:30',
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $fresh = $serviceRequest->fresh();

        $this->assertSame('2026-03-15 08:30:00', $fresh->created_at->format('Y-m-d H:i:s'));
        $this->assertSame([$data['marchCut']->id], $fresh->cuts()->pluck('cuts.id')->all());
    }
}
