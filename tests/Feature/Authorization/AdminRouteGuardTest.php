<?php

namespace Tests\Feature\Authorization;

use App\Models\OperationalAlert;
use App\Models\ServiceRequest;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §10.3 — Blindaje de administración (middleware role:admin) + fail-closed por
 * adivinación de URL + badge APIs scopeadas + web-api.php (casos 7-18c).
 *
 * Con un técnico autenticado (y workspace seleccionado para pasar
 * EnsureWorkspaceSelected) se espera 403 en los grupos administrativos; con
 * admin, 200/redirect válidos.
 */
class AdminRouteGuardTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIsolationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedIsolationWorld();
    }

    private function asTechnician()
    {
        return $this->actingAs($this->t1User)->withSession($this->workspaceSession());
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession($this->workspaceSession());
    }

    // ---- Caso 7: usuarios ----
    public function test_users_index_forbidden_for_technician_ok_for_admin(): void
    {
        $this->asTechnician()->get(route('users.index'))->assertForbidden();
        $this->asAdmin()->get(route('users.index'))->assertOk();
    }

    // ---- Caso 8: catálogo (entidades/contratos/familias/servicios/subservicios) ----
    public function test_catalog_indexes_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('companies.index'))->assertForbidden();
        $this->asTechnician()->get(route('contracts.index'))->assertForbidden();
        $this->asTechnician()->get(route('service-families.index'))->assertForbidden();
        $this->asTechnician()->get(route('services.index'))->assertForbidden();
        $this->asTechnician()->get(route('sub-services.index'))->assertForbidden();
    }

    // ---- Caso 9: SLAs ----
    public function test_slas_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('slas.index'))->assertForbidden();
        $this->asTechnician()->post(route('slas.create-from-modal'), [])->assertForbidden();
    }

    // ---- Caso 10: settings ----
    public function test_settings_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('settings.edit'))->assertForbidden();
        $this->asTechnician()->put(route('settings.update'), ['base_path' => '/x'])->assertForbidden();
    }

    // ---- Caso 11: solicitantes ----
    public function test_requester_management_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('requester-management.requesters.index'))->assertForbidden();
    }

    // ---- Caso 12: técnicos (CRUD + toggle-admin) ----
    public function test_technicians_admin_group_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('technicians.index'))->assertForbidden();
        $this->asTechnician()->get(route('technicians.create'))->assertForbidden();
        $this->asTechnician()->patch(route('technicians.toggle-admin', $this->t2))->assertForbidden();
    }

    // ---- Caso 13: reportes ----
    public function test_reports_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('reports.index'))->assertForbidden();
        $this->asTechnician()->get(route('reports.timeline.index'))->assertForbidden();
        $this->asTechnician()->get(route('reports.cuts.index'))->assertForbidden();
    }

    // ---- Caso 14: proyectos ----
    public function test_projects_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('projects.index'))->assertForbidden();
    }

    // ---- Caso 15: alertas operativas (panel) + indicadores ----
    public function test_operational_alerts_index_and_metrics_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('operational-alerts.index'))->assertForbidden();
        $this->asTechnician()->get(route('performance-metrics.index'))->assertForbidden();
    }

    // ---- Caso 16: tareas estándar ----
    public function test_standard_tasks_forbidden_for_technician(): void
    {
        $this->asTechnician()->get(route('standard-tasks.index'))->assertForbidden();
    }

    // ---- Caso 18: fail-closed por adivinación de URL (asignación/reasignación) ----
    public function test_url_guessing_assign_and_reassign_are_forbidden_for_technician(): void
    {
        // Task-A propia de T1; aún así assign/reassign son admin-only.
        $task = $this->makeTask($this->t1);
        $this->asTechnician()
            ->post(route('tasks.assign', $task), ['technician_id' => $this->t1->id])
            ->assertForbidden();

        $srOwn = $this->makeServiceRequest($this->t1User->id);
        $this->asTechnician()
            ->post(route('service-requests.reassign-submit', $srOwn), ['assigned_to' => $this->t2User->id])
            ->assertForbidden();
    }

    // ---- Caso 18b: badge APIs NO 403 para técnico y NO filtran alertas ajenas ----
    public function test_badge_apis_are_reachable_and_scoped_for_technician(): void
    {
        $srOwn = $this->makeServiceRequest($this->t1User->id);
        $srForeign = $this->makeServiceRequest($this->t2User->id);

        // Alerta de SR propia (contará para T1).
        $this->makeAlert(ServiceRequest::class, $srOwn->id, 'Alerta SR propia');
        // Alerta de SR ajena (NO debe contar para T1).
        $this->makeAlert(ServiceRequest::class, $srForeign->id, 'Alerta SR ajena');

        // Task ajena (de T2) -> alerta ajena.
        $taskForeign = $this->makeTask($this->t2, $srForeign);
        $this->makeAlert(Task::class, $taskForeign->id, 'Alerta Task ajena');

        // Recordatorio propio (alertable_type=User, alertable_id = T1) y uno ajeno.
        $this->makeAlert(\App\Models\User::class, $this->t1User->id, 'Recordatorio propio');
        $this->makeAlert(\App\Models\User::class, $this->t2User->id, 'Recordatorio ajeno');

        $unread = $this->asTechnician()->getJson(route('operational-alerts.api.unread-count'));
        $unread->assertOk();
        // T1 solo ve: SR propia + recordatorio propio = 2.
        $this->assertSame(2, $unread->json('unread'));

        $recent = $this->asTechnician()->getJson(route('operational-alerts.api.recent'));
        $recent->assertOk();
        $titles = collect($recent->json('alerts'))->pluck('title')->all();
        $this->assertContains('Alerta SR propia', $titles);
        $this->assertContains('Recordatorio propio', $titles);
        $this->assertNotContains('Alerta SR ajena', $titles);
        $this->assertNotContains('Alerta Task ajena', $titles);
        $this->assertNotContains('Recordatorio ajeno', $titles);

        // El panel completo sigue siendo admin-only.
        $this->asTechnician()->get(route('operational-alerts.index'))->assertForbidden();

        // El layout renderizado para el técnico NO contiene el ELEMENTO navAlertBell
        // (el id solo existe dentro del bloque @if(isAdmin()); el script de polling
        // menciona el id por getElementById, por eso se asierta sobre el atributo).
        $this->asTechnician()->get(route('my-space.index'))->assertDontSee('id="navAlertBell"', false);
    }

    // ---- Caso 18c: web-api.php ----
    public function test_by_technician_api_enforces_identity(): void
    {
        // Técnico con el id de OTRO técnico -> 403.
        $this->asTechnician()
            ->getJson(route('api.service-requests.by-technician', $this->t2->id))
            ->assertForbidden();

        // Técnico con SU propio id -> 200 (solo sus SRs).
        $this->asTechnician()
            ->getJson(route('api.service-requests.by-technician', $this->t1->id))
            ->assertOk();

        // Admin con cualquier id -> 200.
        $this->asAdmin()
            ->getJson(route('api.service-requests.by-technician', $this->t2->id))
            ->assertOk();
    }

    public function test_catalog_write_apis_are_admin_only(): void
    {
        $this->asTechnician()->postJson(route('api.departments.quick-create'), [])->assertForbidden();
        $this->asTechnician()->postJson(route('api.requesters.quick-create'), [])->assertForbidden();
        $this->asTechnician()->postJson(route('api.service-subservices.find-or-create'), [])->assertForbidden();
    }

    public function test_read_only_loaders_are_reachable_for_technician(): void
    {
        // Loaders operativos del formulario "Crear Solicitud": NO deben ser 403.
        $this->asTechnician()
            ->getJson(route('api.service-families.services', $this->subService->service->service_family_id))
            ->assertStatus(200);
        $this->asTechnician()
            ->getJson(route('api.services.sub-services', $this->subService->service_id))
            ->assertStatus(200);
        $this->asTechnician()
            ->getJson(route('api.sub-services.slas.get', $this->subService->id))
            ->assertStatus(200);
        $this->asTechnician()
            ->getJson(route('api.sub-services.search', ['q' => 'ISO']))
            ->assertStatus(200);
    }

    public function test_requirements_index_is_admin_only(): void
    {
        $this->asTechnician()->get(route('requirements.index'))->assertForbidden();
        $this->asAdmin()->get(route('requirements.index'))->assertOk();
    }

    private function makeAlert(string $alertableType, int $alertableId, string $title): OperationalAlert
    {
        return OperationalAlert::create([
            'alertable_type' => $alertableType,
            'alertable_id' => $alertableId,
            'alert_type' => OperationalAlert::TYPE_REMINDER,
            'severity' => OperationalAlert::SEVERITY_MEDIUM,
            'title' => $title,
            'message' => $title,
            'is_read' => false,
            'is_dismissed' => false,
            'is_resolved' => false,
            'alert_at' => now()->subMinute(),
        ]);
    }
}
