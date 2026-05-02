<?php

namespace Tests\Feature\Tasks;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSubtaskReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reorders_subtasks_for_a_task(): void
    {
        $user = User::factory()->create();
        $companyId = $user->companies()->value('companies.id');

        $task = Task::create([
            'type' => 'regular',
            'title' => 'Tarea para ordenar subtareas',
            'priority' => 'medium',
            'status' => 'pending',
            'estimated_duration_minutes' => 25,
        ]);

        $first = $task->subtasks()->create([
            'title' => 'Primera',
            'priority' => 'medium',
            'order' => 1,
            'estimated_minutes' => 25,
        ]);

        $second = $task->subtasks()->create([
            'title' => 'Segunda',
            'priority' => 'high',
            'order' => 2,
            'estimated_minutes' => 30,
        ]);

        $third = $task->subtasks()->create([
            'title' => 'Tercera',
            'priority' => 'low',
            'order' => 3,
            'estimated_minutes' => 20,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_company_id' => $companyId])
            ->postJson(route('tasks.subtasks.reorder', $task), [
                'subtask_ids' => [$third->id, $first->id, $second->id],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('subtasks', [
            'id' => $third->id,
            'order' => 1,
        ]);

        $this->assertDatabaseHas('subtasks', [
            'id' => $first->id,
            'order' => 2,
        ]);

        $this->assertDatabaseHas('subtasks', [
            'id' => $second->id,
            'order' => 3,
        ]);

        $orderedIds = Subtask::query()
            ->where('task_id', $task->id)
            ->ordered()
            ->pluck('id')
            ->all();

        $this->assertSame([$third->id, $first->id, $second->id], $orderedIds);
    }
}
