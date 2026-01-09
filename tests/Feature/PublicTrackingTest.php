<?php

namespace Tests\Feature;

use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimalServiceTree(): array
    {
        $user = User::factory()->create();
        $requester = Requester::factory()->create([
            'email' => 'requester@example.com',
        ]);

        $family = ServiceFamily::create([
            'name' => 'Familia Test',
            'code' => 'FAMT',
            'description' => 'Test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Servicio Test',
            'code' => 'SERVT',
            'description' => 'Test',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Subservicio Test',
            'code' => 'SUBT',
            'description' => 'Test',
            'is_active' => true,
            'order' => 0,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA Test',
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

        $serviceRequest = ServiceRequest::create([
            'ticket_number' => 'SR-1234',
            'sla_id' => $sla->id,
            'sub_service_id' => $subService->id,
            'requested_by' => $user->id,
            'assigned_to' => null,
            'title' => 'Solicitud de prueba',
            'description' => 'Descripción',
            'criticality_level' => 'MEDIA',
            'status' => 'PENDIENTE',
            'requester_id' => $requester->id,
            'entry_channel' => ServiceRequest::ENTRY_CHANNEL_DIGITAL_EMAIL,
            'web_routes' => [],
            'is_reportable' => true,
        ]);

        return compact('user', 'requester', 'family', 'service', 'subService', 'sla', 'serviceRequest');
    }

    public function test_public_show_is_blocked_without_prior_search(): void
    {
        $data = $this->seedMinimalServiceTree();

        $response = $this->get('/consultar/' . $data['serviceRequest']->ticket_number);

        $response
            ->assertRedirect('/consultar')
            ->assertSessionHas('error');
    }

    public function test_public_ticket_search_grants_temporary_access_to_show(): void
    {
        $data = $this->seedMinimalServiceTree();

        $response = $this->followingRedirects()->post('/consultar/search', [
            'query' => $data['serviceRequest']->ticket_number,
            'type' => 'ticket',
        ]);

        $response->assertOk();
    }

    public function test_public_email_search_allows_show_for_matching_requester(): void
    {
        $data = $this->seedMinimalServiceTree();

        $this->post('/consultar/search', [
            'query' => $data['requester']->email,
            'type' => 'email',
        ])->assertOk();

        $this->get('/consultar/' . $data['serviceRequest']->ticket_number)->assertOk();
    }
}
