# Implementation Plan: Resolve Button Validation

## Overview

Add subtask-completion validation to the "Resolver Solicitud" button. A new `hasCompletedSubtask` method in `ServiceRequestViewService` checks if at least one subtask has `is_completed = true`. The resolve button is shown only when both evidence AND subtask conditions are met. When subtask validation fails, an anchor button scrolls the user to the tasks panel.

## Tasks

- [x] 1. Add `hasCompletedSubtask` method to ServiceRequestViewService
  - [x] 1.1 Implement `hasCompletedSubtask` method in `app/Services/ServiceRequestViewService.php`
    - Add public method that accepts a `ServiceRequest` and returns `bool`
    - Check if `tasks` relation is loaded; if not, eager-load with `subtasks`
    - Iterate all tasks and their subtasks, return `true` if any subtask has `is_completed = true`
    - Return `false` when no tasks exist, no subtasks exist, or all subtasks are incomplete
    - _Requirements: 4.1, 4.2, 4.3_

  - [ ]* 1.2 Write property test for subtask completion validation
    - **Property 1: Subtask completion validation correctness**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 4.1, 4.3**

  - [ ]* 1.3 Write property test for independence from parent task status
    - **Property 3: Validation independence from parent task status**
    - **Validates: Requirements 4.2**

  - [ ]* 1.4 Write unit tests for `hasCompletedSubtask`
    - Test returns `false` for service request with no tasks
    - Test returns `false` for tasks with no subtasks
    - Test returns `false` when all subtasks have `is_completed = false`
    - Test returns `true` when at least one subtask has `is_completed = true`
    - Test works correctly when relations are already loaded vs not loaded
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 2. Checkpoint - Verify service method
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Modify Workflow Actions Component to use compound validation
  - [x] 3.1 Update `@php` block in `resources/views/components/service-requests/show/header/workflow-actions.blade.php`
    - Add `$hasCompletedSubtask = $viewService->hasCompletedSubtask($serviceRequest);`
    - Change the `EN_PROCESO` resolve action's `'condition'` from `$canResolveByEvidence` to `$canResolveByEvidence && $hasCompletedSubtask`
    - _Requirements: 1.1, 1.3, 3.1_

  - [x] 3.2 Add anchor button rendering in the `@else` (disabled) block for the resolve action
    - When `$canResolveByEvidence` is `true` but `$hasCompletedSubtask` is `false`, render an anchor `<a>` element instead of the disabled button
    - The anchor href should be `#tasks-panel-{{ $serviceRequest->id }}`
    - Add `onclick` with `event.preventDefault()` and `document.getElementById('tasks-panel-{{ $serviceRequest->id }}').scrollIntoView({ behavior: 'smooth', block: 'start' })`
    - Use the same styling classes from `$resolveActionClasses(['appearance' => 'primary'])`
    - Show icon `fa-arrow-down` and text "Completar Tareas"
    - Add `aria-label="Ir a Tareas Asociadas"`
    - When `$canResolveByEvidence` is `false`, keep the existing disabled button with evidence message
    - _Requirements: 2.1, 2.3, 2.4, 2.5, 3.3, 3.4_

  - [ ]* 3.3 Write property test for compound resolve condition
    - **Property 2: Compound resolve condition requires both validations**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**

- [x] 4. Modify Tasks Panel Component to use compound validation
  - [x] 4.1 Update `@php` block in `resources/views/components/service-requests/show/content/tasks-panel.blade.php`
    - Add `$hasCompletedSubtask = $viewService->hasCompletedSubtask($serviceRequest);`
    - Create compound variable `$canResolveInTasksPanel = $isInProgress && $canResolveByEvidence && $hasCompletedSubtask;`
    - _Requirements: 1.2, 3.2_

  - [x] 4.2 Update the resolve button conditional in the tasks panel template
    - Change `@if($isInProgress && $canResolveByEvidence)` to `@if($canResolveInTasksPanel)`
    - No anchor button needed in tasks panel (user is already viewing the tasks section)
    - _Requirements: 1.4, 2.2, 3.2_

- [x] 5. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- No database migrations are needed — uses existing `is_completed` field on `subtasks` table
- The implementation language is PHP (Laravel/Blade) as determined by the existing codebase

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.4"] },
    { "id": 2, "tasks": ["3.1", "4.1"] },
    { "id": 3, "tasks": ["3.2", "3.3", "4.2"] }
  ]
}
```
