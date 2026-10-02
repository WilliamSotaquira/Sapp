<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Propiedad de una tarea para un técnico: Task.technician_id === user->technician->id.
     * Sin perfil técnico, no posee ninguna tarea (fail-closed).
     */
    private function ownsTask(User $user, Task $task): bool
    {
        $techId = $user->technician?->id;

        return $techId !== null && (int) $task->technician_id === (int) $techId;
    }

    public function view(User $user, Task $task): bool             { return $this->ownsTask($user, $task); }
    public function update(User $user, Task $task): bool           { return $this->ownsTask($user, $task); }
    public function start(User $user, Task $task): bool            { return $this->ownsTask($user, $task); }
    public function complete(User $user, Task $task): bool         { return $this->ownsTask($user, $task); }
    public function block(User $user, Task $task): bool            { return $this->ownsTask($user, $task); }
    public function unblock(User $user, Task $task): bool          { return $this->ownsTask($user, $task); }
    public function reschedule(User $user, Task $task): bool       { return $this->ownsTask($user, $task); }
    public function updateDuration(User $user, Task $task): bool   { return $this->ownsTask($user, $task); }
    public function schedule(User $user, Task $task): bool         { return $this->ownsTask($user, $task); }
    public function manageSubtasks(User $user, Task $task): bool   { return $this->ownsTask($user, $task); }
    public function manageChecklists(User $user, Task $task): bool { return $this->ownsTask($user, $task); }

    public function assign(User $user, Task $task): bool { return false; } // solo admin
    public function delete(User $user, Task $task): bool { return false; } // solo admin

    public function create(User $user): bool { return true; } // validación fina en el controlador
}
