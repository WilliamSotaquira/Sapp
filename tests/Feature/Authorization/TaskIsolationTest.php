<?php

namespace Tests\Feature\Authorization;

use App\Http\Controllers\TaskController;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * §10.5 — Aislamiento de datos de tareas (TaskPolicy + scoping), casos 27-34.
 * Setup: Task-A (técnico T1), Task-B (técnico T2). T1 ve/opera SOLO Task-A.
 * assign/suggest/delete son admin-only. apply-auto-queue y enqueue rechazan
 * el technician_id ajeno. Sin perfil -> index 0 filas y apply-auto-queue 403.
 */
class TaskIsolationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIsolationFixtures;

    protected Task $taskA;
    protected Task $taskB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedIsolationWorld();

        $this->taskA = $this->makeTask($this->t1, null, ['task_code' => 'TSK-A-0001']);
        $this->taskB = $this->makeTask($this->t2, null, ['task_code' => 'TSK-B-0001']);
    }

    private function asT1()
    {
        return $this->actingAs($this->t1User)->withSession($this->workspaceSession());
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession($this->workspaceSession());
    }

    // 27 — index incluye Task-A, excluye Task-B.
    public function test_index_includes_own_excludes_foreign(): void
    {
        $response = $this->asT1()->get(route('tasks.index'));
        $response->assertOk();
        $response->assertSee('TSK-A-0001');
        $response->assertDontSee('TSK-B-0001');
    }

    // 28 — index?technician_id=T2 sigue excluyendo Task-B (parámetro ignorado).
    public function test_index_ignores_foreign_technician_id_param(): void
    {
        $response = $this->asT1()->get(route('tasks.index', ['technician_id' => $this->t2->id]));
        $response->assertOk();
        $response->assertSee('TSK-A-0001');
        $response->assertDontSee('TSK-B-0001');
    }

    // 29 — show Task-B -> 403 (hoy sería 200: regresión fijada).
    public function test_show_foreign_is_forbidden(): void
    {
        $this->asT1()->get(route('tasks.show', $this->taskB))->assertForbidden();
    }

    // 30 — complete/start/block Task-B -> 403.
    public function test_lifecycle_actions_on_foreign_are_forbidden(): void
    {
        $this->asT1()->post(route('tasks.complete', $this->taskB))->assertForbidden();
        $this->asT1()->post(route('tasks.start', $this->taskB))->assertForbidden();
        $this->asT1()->post(route('tasks.block', $this->taskB), ['reason' => 'x'])->assertForbidden();
    }

    // 30b — toggle-status y subtasks.toggle sobre Task-B -> 403; Task-A -> no 403.
    // Finding #7: ambos handlers (toggleSubtask y toggleSubtaskStatus) deben 403.
    public function test_toggle_status_and_subtask_toggle_isolation(): void
    {
        $this->asT1()->post(route('tasks.toggle-status', $this->taskB))->assertForbidden();

        $subtaskB = Subtask::create(['task_id' => $this->taskB->id, 'title' => 'ST-B', 'status' => 'pending', 'order' => 1]);
        $this->asT1()
            ->post(route('tasks.subtasks.toggle', [$this->taskB, $subtaskB]))
            ->assertForbidden();

        // El OTRO handler del nombre duplicado (toggleSubtaskStatus) también debe
        // autorizar: invocación directa como T1 sobre Task-B -> AuthorizationException.
        $this->actingAs($this->t1User);
        $threw = false;
        try {
            app(TaskController::class)->toggleSubtaskStatus($this->taskB, $subtaskB);
        } catch (AuthorizationException $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'toggleSubtaskStatus debe autorizar (manageSubtasks) y lanzar en Task ajena.');

        // Sobre Task-A propia -> no 403.
        $subtaskA = Subtask::create(['task_id' => $this->taskA->id, 'title' => 'ST-A', 'status' => 'pending', 'order' => 1]);
        $resp = $this->asT1()->post(route('tasks.subtasks.toggle', [$this->taskA, $subtaskA]));
        $this->assertNotSame(403, $resp->getStatusCode());
    }

    // 30c — apply-auto-queue y enqueue con technician_id ajeno.
    public function test_apply_auto_queue_rejects_foreign_technician(): void
    {
        $date = now()->addDay()->toDateString();

        // technician_id = T2 -> 403.
        $this->asT1()->post(route('tasks.apply-auto-queue'), [
            'scheduled_date' => $date,
            'technician_id' => $this->t2->id,
        ])->assertForbidden();

        // technician_id = T1 -> no 403 (reordena su propia cola).
        $own = $this->asT1()->post(route('tasks.apply-auto-queue'), [
            'scheduled_date' => $date,
            'technician_id' => $this->t1->id,
        ]);
        $this->assertNotSame(403, $own->getStatusCode());
    }

    public function test_enqueue_day_isolation(): void
    {
        // enqueue-day sobre Task-A propia pero con technician_id=T2 -> rechazado.
        // La Policy 'schedule' pasa (Task-A es de T1), pero la validación de
        // pertenencia impide encolar en la agenda de otro: 422 (JSON) y la tarea
        // NO cambia de técnico.
        $foreign = $this->asT1()->postJson(route('tasks.enqueue-day', $this->taskA), [
            'scheduled_date' => now()->addDay()->toDateString(),
            'technician_id' => $this->t2->id,
        ]);
        $foreign->assertStatus(422);
        $this->assertSame($this->t1->id, (int) $this->taskA->fresh()->technician_id);

        // enqueue-day sobre Task-B (de T2) -> 403.
        $this->asT1()->post(route('tasks.enqueue-day', $this->taskB), [
            'scheduled_date' => now()->addDay()->toDateString(),
            'technician_id' => $this->t1->id,
        ])->assertForbidden();
    }

    // schedule mutations Task-B -> 403; Task-A -> no 403.
    public function test_schedule_mutations_isolation(): void
    {
        $this->asT1()->post(route('tasks.unschedule', $this->taskB))->assertForbidden();
        $this->asT1()->post(route('tasks.schedule-quick', $this->taskB), [])->assertForbidden();
        $this->asT1()->post(route('tasks.clear-schedule', $this->taskB))->assertForbidden();

        $resp = $this->asT1()->post(route('tasks.unschedule', $this->taskA));
        $this->assertNotSame(403, $resp->getStatusCode());
    }

    // PUT/DELETE Task-B -> 403; DELETE Task-A -> 403 (delete admin-only); admin -> no 403.
    public function test_update_and_delete_isolation(): void
    {
        $this->asT1()->put(route('tasks.update', $this->taskB), ['title' => 'x'])->assertForbidden();
        $this->asT1()->delete(route('tasks.destroy', $this->taskB))->assertForbidden();

        // delete sobre Task-A propia también 403 (delete es admin-only).
        $this->asT1()->delete(route('tasks.destroy', $this->taskA))->assertForbidden();

        // admin puede borrar.
        $adminResp = $this->asAdmin()->delete(route('tasks.destroy', $this->taskB));
        $this->assertNotSame(403, $adminResp->getStatusCode());
    }

    // 31 — Task-A propia: complete/start -> no 403.
    public function test_actions_on_own_task_are_allowed(): void
    {
        $resp = $this->asT1()->post(route('tasks.start', $this->taskA));
        $this->assertNotSame(403, $resp->getStatusCode());
    }

    // 32 — assign/suggest-assignment Task-B -> 403; admin -> no 403.
    public function test_assign_and_suggest_are_admin_only(): void
    {
        $this->asT1()->post(route('tasks.assign', $this->taskB), ['technician_id' => $this->t1->id])->assertForbidden();
        $this->asT1()->get(route('tasks.suggest-assignment', $this->taskB))->assertForbidden();

        $adminResp = $this->asAdmin()->get(route('tasks.suggest-assignment', $this->taskB));
        $this->assertNotSame(403, $adminResp->getStatusCode());
    }

    // 33 — técnico role=technician SIN perfil -> index 0 filas; apply-auto-queue -> 403.
    public function test_technician_without_profile_sees_no_tasks_and_cannot_auto_queue(): void
    {
        $noProfile = User::factory()->technicianRole()->withoutCompany()->create();
        $noProfile->companies()->syncWithoutDetaching([$this->entity->id]);

        $response = $this->actingAs($noProfile)->withSession($this->workspaceSession())
            ->get(route('tasks.index'));
        $response->assertOk();
        $items = $response->viewData('tasks');
        $this->assertCount(0, $items->items());

        $this->actingAs($noProfile)->withSession($this->workspaceSession())
            ->post(route('tasks.apply-auto-queue'), [
                'scheduled_date' => now()->addDay()->toDateString(),
                'technician_id' => $this->t1->id,
            ])->assertForbidden();
    }

    // 34 — MySpace.completeTask sobre tarea ajena -> 403.
    public function test_myspace_complete_foreign_task_is_forbidden(): void
    {
        $this->asT1()->post(route('my-space.tasks.complete', $this->taskB))->assertForbidden();
    }
}
