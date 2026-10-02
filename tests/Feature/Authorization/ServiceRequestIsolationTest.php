<?php

namespace Tests\Feature\Authorization;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §10.4 — Aislamiento de datos de solicitudes (ServiceRequestPolicy + scoping),
 * casos 19-26b. Setup: entidad E; T1 y T2 ambos atienden E; SR-A asignada a T1,
 * SR-B asignada a T2. T1 ve/opera SOLO SR-A, nunca SR-B. Reasignar/asignar son
 * admin-only. Los asserts solo miran SR-A/SR-B controladas (corrige finding #6).
 */
class ServiceRequestIsolationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIsolationFixtures;

    protected ServiceRequest $srA;
    protected ServiceRequest $srB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedIsolationWorld();

        $this->srA = $this->makeServiceRequest($this->t1User->id, ['ticket_number' => 'SR-A-0001']);
        $this->srB = $this->makeServiceRequest($this->t2User->id, ['ticket_number' => 'SR-B-0001']);
    }

    private function asT1()
    {
        return $this->actingAs($this->t1User)->withSession($this->workspaceSession());
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession($this->workspaceSession());
    }

    // 19 — index incluye SR-A y NO SR-B.
    public function test_index_includes_own_excludes_foreign(): void
    {
        $response = $this->asT1()->get(route('service-requests.index'));
        $response->assertOk();
        $response->assertSee('SR-A-0001');
        $response->assertDontSee('SR-B-0001');
    }

    // 20 — index?scope=all&company_id=E sigue sin ver SR-B.
    public function test_index_scope_all_cannot_bypass_scoping(): void
    {
        $response = $this->asT1()->get(route('service-requests.index', [
            'scope' => 'all',
            'company_id' => $this->entity->id,
        ]));
        $response->assertOk();
        $response->assertSee('SR-A-0001');
        $response->assertDontSee('SR-B-0001');
    }

    // 21 — show SR-B -> 403 (binding endurecido).
    public function test_show_foreign_is_forbidden(): void
    {
        $this->asT1()->get(route('service-requests.show', $this->srB))->assertForbidden();
    }

    // 22 — accept SR-B -> 403.
    public function test_accept_foreign_is_forbidden(): void
    {
        $this->asT1()->patch(route('service-requests.accept', $this->srB))->assertForbidden();
    }

    // 23 — accept SR-A -> OK (no 403; el binding + ability lo permiten al dueño).
    public function test_accept_own_is_allowed(): void
    {
        $response = $this->asT1()->patch(route('service-requests.accept', $this->srA));
        $this->assertNotSame(403, $response->getStatusCode());
    }

    // 24 — reassign-submit SR-A -> 403 (reasignar es admin).
    public function test_reassign_own_is_forbidden_for_technician(): void
    {
        $this->asT1()
            ->post(route('service-requests.reassign-submit', $this->srA), ['assigned_to' => $this->t2User->id])
            ->assertForbidden();
    }

    // 24b — quick-assign / quick-assign-requester SR-A -> 403; admin -> OK (no 403).
    public function test_quick_assign_is_admin_only(): void
    {
        $this->asT1()->post(route('service-requests.quick-assign', $this->srA), [])->assertForbidden();
        $this->asT1()->post(route('service-requests.quick-assign-requester', $this->srA), [])->assertForbidden();

        $adminResp = $this->asAdmin()->post(route('service-requests.quick-assign', $this->srA), [
            'assigned_to' => $this->t1User->id,
        ]);
        $this->assertNotSame(403, $adminResp->getStatusCode());
    }

    // 25 — admin ve SR-A y SR-B.
    public function test_admin_sees_both_requests(): void
    {
        $response = $this->asAdmin()->get(route('service-requests.index', [
            'scope' => 'all',
            'company_id' => $this->entity->id,
        ]));
        $response->assertOk();
        $response->assertSee('SR-A-0001');
        $response->assertSee('SR-B-0001');
    }

    // 26 — estadísticas del índice para T1 reflejan solo SR-A (no filtran SR-B).
    public function test_index_stats_reflect_only_own(): void
    {
        $response = $this->asT1()->get(route('service-requests.index'));
        $response->assertOk();

        $items = $response->viewData('serviceRequests');
        $ids = collect($items->items())->pluck('id')->all();
        $this->assertContains($this->srA->id, $ids);
        $this->assertNotContains($this->srB->id, $ids);
    }

    // 26b — mutaciones sobre SR ajena -> 403; sobre SR-A propia -> no 403.
    public function test_mutations_on_foreign_request_are_forbidden(): void
    {
        $this->asT1()->post(route('service-requests.finalize-non-viable', $this->srB), [])->assertForbidden();
        $this->asT1()->post(route('service-requests.generate-resolution', $this->srB), [])->assertForbidden();
        $this->asT1()->post(route('service-requests.quick-task', $this->srB), [])->assertForbidden();
    }

    public function test_quick_task_on_own_request_is_not_forbidden(): void
    {
        $response = $this->asT1()->post(route('service-requests.quick-task', $this->srA), [
            'title' => 'Tarea rápida desde SR propia',
        ]);
        $this->assertNotSame(403, $response->getStatusCode());
    }
}
