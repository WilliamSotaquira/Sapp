# Design Document

## Overview

This feature adds subtask-completion validation to the "Resolver Solicitud" (Resolve Request) button in the service request detail view. The validation ensures at least one subtask has `is_completed = true` before allowing resolution. When the condition is not met, an anchor button replaces the resolve button, guiding the user to the tasks section via smooth scroll.

The validation is implemented as a new helper method in `ServiceRequestViewService` and integrated into both the header workflow-actions component and the tasks-panel component. It works alongside the existing `canResolveByEvidence` condition using AND logic.

## Architecture

### Component Interaction

```
┌─────────────────────────────────────────────────────────┐
│              Service Request Detail View                  │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────────────────────────────────────────────┐    │
│  │  Workflow Actions Component (Header)             │    │
│  │  ┌───────────────────────────────────────────┐  │    │
│  │  │ canResolve = evidence AND subtaskValid    │  │    │
│  │  │ → true:  Show Resolve Button              │  │    │
│  │  │ → false (subtask): Show Anchor Button     │  │    │
│  │  │ → false (evidence): Show Disabled Button  │  │    │
│  │  └───────────────────────────────────────────┘  │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ┌─────────────────────────────────────────────────┐    │
│  │  Tasks Panel Component (Content)                 │    │
│  │  id="tasks-panel-{serviceRequestId}"             │    │
│  │  ┌───────────────────────────────────────────┐  │    │
│  │  │ canResolve = evidence AND subtaskValid    │  │    │
│  │  │ → true:  Show Resolve Button              │  │    │
│  │  │ → false: Hide Resolve Button              │  │    │
│  │  └───────────────────────────────────────────┘  │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Data Flow

```
ServiceRequest
  └── tasks() [HasMany]
        └── subtasks() [HasMany]
              └── is_completed (boolean)

ServiceRequestViewService::hasCompletedSubtask($serviceRequest): bool
  → Traverses all tasks → all subtasks → checks is_completed
  → Returns true if ANY subtask has is_completed = true
```

## Components and Interfaces

### 1. ServiceRequestViewService (New Method)

**File:** `app/Services/ServiceRequestViewService.php`

A new public method `hasCompletedSubtask` is added to the existing service class.

```php
/**
 * Verifica si al menos una subtarea de la solicitud tiene is_completed = true.
 * Independiente del estado de la tarea padre.
 */
public function hasCompletedSubtask(ServiceRequest $serviceRequest): bool
{
    $tasks = $serviceRequest->relationLoaded('tasks')
        ? $serviceRequest->tasks
        : $serviceRequest->tasks()->with('subtasks')->get();

    foreach ($tasks as $task) {
        $subtasks = $task->relationLoaded('subtasks')
            ? $task->subtasks
            : $task->subtasks()->get();

        foreach ($subtasks as $subtask) {
            if ($subtask->is_completed) {
                return true;
            }
        }
    }

    return false;
}
```

### 2. Workflow Actions Component (Modified)

**File:** `resources/views/components/service-requests/show/header/workflow-actions.blade.php`

Changes to the `@php` block:

```php
$hasCompletedSubtask = $viewService->hasCompletedSubtask($serviceRequest);
$canResolveByEvidence = ($serviceRequest->is_reportable === false)
    || $viewService->getResolvableEvidenceCount($serviceRequest) > 0;
$canResolve = $canResolveByEvidence && $hasCompletedSubtask;
```

The `EN_PROCESO` workflow config changes the resolve action's `condition` to use the compound `$canResolve` variable. When `$canResolveByEvidence` is true but `$hasCompletedSubtask` is false, an anchor button is rendered instead of the disabled button.

**Anchor Button Logic (in the template):**

```blade
@if (!$canResolveByEvidence)
    {{-- Existing disabled button with evidence message --}}
    <button type="button" disabled ...>
        Resolver Solicitud
    </button>
@elseif (!$hasCompletedSubtask)
    {{-- New anchor button to tasks section --}}
    <a href="#tasks-panel-{{ $serviceRequest->id }}"
       onclick="event.preventDefault(); document.getElementById('tasks-panel-{{ $serviceRequest->id }}').scrollIntoView({ behavior: 'smooth', block: 'start' })"
       class="{{ $resolveActionClasses(['appearance' => 'primary']) }}"
       aria-label="Ir a Tareas Asociadas">
        <i class="fas fa-arrow-down mr-2 flex-shrink-0 text-[13px] transition-transform group-hover:scale-105" aria-hidden="true"></i>
        <span class="line-clamp-2 text-center leading-tight">Completar Tareas</span>
    </a>
@else
    {{-- Normal resolve button --}}
@endif
```

### 3. Tasks Panel Component (Modified)

**File:** `resources/views/components/service-requests/show/content/tasks-panel.blade.php`

Changes to the `@php` block:

```php
$hasCompletedSubtask = $viewService->hasCompletedSubtask($serviceRequest);
$canResolveInTasksPanel = $isInProgress && $canResolveByEvidence && $hasCompletedSubtask;
```

The existing resolve button section changes from:
```blade
@if($isInProgress && $canResolveByEvidence)
```
to:
```blade
@if($canResolveInTasksPanel)
```

When `$isInProgress && $canResolveByEvidence && !$hasCompletedSubtask`, no resolve button or anchor is shown in the tasks panel (the user is already looking at the tasks section).

### Interfaces

#### ServiceRequestViewService

```php
interface ServiceRequestViewServiceInterface
{
    // Existing methods...
    public function getResolvableEvidenceCount(ServiceRequest $request): int;
    public function canResolve(ServiceRequest $request): bool;

    // New method
    public function hasCompletedSubtask(ServiceRequest $serviceRequest): bool;
}
```

### Component Props (unchanged)

Both components continue to receive `$serviceRequest` as their primary prop. No new props are needed since the validation is computed internally via the view service.

## Data Models

### Existing Models (No Changes)

**Subtask Model** — uses existing `is_completed` boolean field:
```php
// Already defined in App\Models\Subtask
protected $casts = [
    'is_completed' => 'boolean',
];
```

**Task Model** — uses existing `subtasks()` relationship:
```php
// Already defined in App\Models\Task
public function subtasks()
{
    return $this->hasMany(Subtask::class);
}
```

**ServiceRequest Model** — uses existing `tasks()` relationship:
```php
// Already defined in App\Models\ServiceRequest
public function tasks()
{
    return $this->hasMany(Task::class, 'service_request_id');
}
```

No database migrations are required.

## Error Handling

| Scenario | Handling |
|----------|----------|
| Service request has no tasks | `hasCompletedSubtask` returns `false` → anchor button shown |
| Tasks exist but have no subtasks | `hasCompletedSubtask` returns `false` → anchor button shown |
| Tasks relation not loaded | Method loads tasks with subtasks eagerly |
| Subtasks relation not loaded on task | Method loads subtasks for each task |
| Service request not in EN_PROCESO | Resolve button section not rendered at all (existing behavior) |

## Testing Strategy

### Unit Tests
- Verify `hasCompletedSubtask` returns `false` for a service request with no tasks
- Verify `hasCompletedSubtask` returns `false` for tasks with no subtasks
- Verify `hasCompletedSubtask` returns `false` when all subtasks have `is_completed = false`
- Verify `hasCompletedSubtask` returns `true` when at least one subtask has `is_completed = true`
- Verify the anchor button renders with correct href and smooth scroll behavior
- Verify the compound condition (evidence AND subtask) produces correct button states

### Property Tests
- Property-based tests for the `hasCompletedSubtask` validation logic with randomly generated task/subtask structures
- Property-based tests for compound boolean logic with random combinations of evidence and subtask conditions
- Property-based tests verifying independence from parent task status field

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Subtask completion validation correctness

*For any* service request with any number of tasks (including zero) and any number of subtasks per task (including zero), `hasCompletedSubtask` SHALL return `true` if and only if there exists at least one subtask across all tasks where `is_completed = true`.

**Validates: Requirements 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 4.1, 4.3**

### Property 2: Compound resolve condition requires both validations

*For any* combination of `canResolveByEvidence` (boolean) and `hasCompletedSubtask` (boolean), the resolve button SHALL be shown enabled only when both values are `true`. When `canResolveByEvidence` is `false`, the disabled button with evidence message is shown. When `canResolveByEvidence` is `true` and `hasCompletedSubtask` is `false`, the anchor button is shown.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

### Property 3: Validation independence from parent task status

*For any* service request where at least one subtask has `is_completed = true`, `hasCompletedSubtask` SHALL return `true` regardless of the `status` field value of the parent task (pending, in_progress, completed, or any other value).

**Validates: Requirements 4.2**
