<?php

namespace Tests\Feature\Authorization;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\Task;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Scaffolding compartido por la matriz de aislamiento (§10.4/§10.5/§10.6).
 *
 * Construye UNA entidad controlada E (empresa + contrato + árbol de servicio +
 * SLA + solicitante) y los técnicos T1/T2 (role=technician + perfil Technician)
 * que atienden E. Deliberadamente se usa el estado withoutCompany() del
 * UserFactory para NO arrastrar la Company espuria del afterCreating y que los
 * asserts de scoping dependan solo de lo que el test adjunta (corrige finding #6).
 */
trait InteractsWithIsolationFixtures
{
    protected Company $entity;
    protected Contract $contract;
    protected SubService $subService;
    protected ServiceLevelAgreement $sla;
    protected Requester $requester;

    protected User $admin;
    protected User $t1User;
    protected User $t2User;
    protected Technician $t1;
    protected Technician $t2;

    protected function seedIsolationWorld(): void
    {
        $this->entity = Company::create([
            'name' => 'Entidad Controlada E',
            'status' => 'active',
        ]);

        $this->contract = Contract::create([
            'company_id' => $this->entity->id,
            'number' => 'C-ISO-001',
            'name' => 'Contrato aislamiento',
            'description' => 'Contrato de prueba de aislamiento',
            'is_active' => true,
        ]);
        $this->entity->update(['active_contract_id' => $this->contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $this->contract->id,
            'name' => 'Familia Aislamiento',
            'code' => 'FISO',
            'description' => 'Familia de prueba',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Aislamiento',
            'code' => 'SISO',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $this->subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Publicacion ISO',
            'code' => 'PUB_ISO',
            'description' => 'Subservicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $slaAttributes = [
            'name' => 'SLA MEDIA ISO',
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
        if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
            $slaAttributes['sub_service_id'] = $this->subService->id;
        }
        $this->sla = ServiceLevelAgreement::create($slaAttributes);

        $this->requester = Requester::factory()->create([
            'company_id' => $this->entity->id,
            'name' => 'Solicitante ISO',
            'email' => 'iso@example.com',
        ]);

        // Admin (dueño del proceso). Fija role=admin explícito.
        $this->admin = User::factory()->admin()->withoutCompany()->create();
        $this->admin->companies()->syncWithoutDetaching([$this->entity->id]);

        // Técnicos T1/T2: role=technician, perfil Technician, ambos atienden E.
        $this->t1User = User::factory()->technicianRole()->withoutCompany()->create();
        $this->t2User = User::factory()->technicianRole()->withoutCompany()->create();

        $this->t1 = Technician::factory()->create(['user_id' => $this->t1User->id]);
        $this->t2 = Technician::factory()->create(['user_id' => $this->t2User->id]);

        // Ambos técnicos atienden la entidad E (pivote company_technician), y sus
        // usuarios pertenecen a E para que EnsureWorkspaceSelected auto-seleccione.
        $this->t1->companies()->syncWithoutDetaching([$this->entity->id]);
        $this->t2->companies()->syncWithoutDetaching([$this->entity->id]);
        $this->t1User->companies()->syncWithoutDetaching([$this->entity->id]);
        $this->t2User->companies()->syncWithoutDetaching([$this->entity->id]);
    }

    /**
     * Crea una SR en la entidad E con el usuario asignado dado.
     * Usa withoutEvents para no disparar la auto-asignación de tareas/cortes.
     */
    protected function makeServiceRequest(?int $assignedToUserId, array $overrides = []): ServiceRequest
    {
        return ServiceRequest::withoutEvents(function () use ($assignedToUserId, $overrides) {
            return ServiceRequest::withoutGlobalScopes()->create(array_merge([
                'company_id' => $this->entity->id,
                'contract_id' => $this->contract->id,
                'requester_id' => $this->requester->id,
                'ticket_number' => 'SR-ISO-' . strtoupper(bin2hex(random_bytes(3))),
                'title' => 'Solicitud de aislamiento',
                'description' => 'Solicitud de prueba de aislamiento.',
                'sub_service_id' => $this->subService->id,
                'sla_id' => $this->sla->id,
                'requested_by' => $this->admin->id,
                'assigned_to' => $assignedToUserId,
                'entry_channel' => 'email_corporativo',
                'criticality_level' => 'MEDIA',
                'status' => 'ACEPTADA',
                'accepted_at' => now(),
                'created_at' => now(),
            ], $overrides));
        });
    }

    /**
     * Crea una Task en la entidad E para el técnico dado.
     */
    protected function makeTask(?Technician $technician, ?ServiceRequest $serviceRequest = null, array $overrides = []): Task
    {
        $serviceRequest ??= $this->makeServiceRequest($technician?->user_id);

        return Task::withoutEvents(function () use ($technician, $serviceRequest, $overrides) {
            return Task::create(array_merge([
                'task_code' => 'TSK-ISO-' . strtoupper(bin2hex(random_bytes(3))),
                'type' => 'regular',
                'title' => 'Tarea de aislamiento',
                'description' => 'Tarea de prueba de aislamiento.',
                'service_request_id' => $serviceRequest->id,
                'technician_id' => $technician?->id,
                'status' => 'pending',
                'priority' => 'medium',
                'scheduled_date' => now()->addDay()->toDateString(),
            ], $overrides));
        });
    }

    /**
     * Sesión con la entidad E seleccionada como workspace.
     */
    protected function workspaceSession(): array
    {
        return [
            'current_company_id' => $this->entity->id,
            'current_contract_id' => $this->contract->id,
        ];
    }
}
