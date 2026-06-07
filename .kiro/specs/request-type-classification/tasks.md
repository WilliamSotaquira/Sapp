# Implementation Plan: Request Type Classification

## Overview

Implementación del sistema de clasificación de tipos de solicitud para SAPP. Se introduce un registro de tipos, el ciclo de vida de reuniones (programación, participantes, evidencia, compromisos), solicitudes derivadas con trazabilidad, y un historial de asignaciones. El diseño es aditivo: tablas y modelos nuevos junto al `ServiceRequest` existente, sin alterar las migraciones ni el workflow actual para solicitudes sin tipo.

## Tasks

- [x] 1. Database migrations and seeder
  - [x] 1.1 Create `request_types` table migration
    - Create migration file in `database/migrations/`
    - Define columns: `id`, `slug` (varchar 50, unique), `name` (varchar 100), `description` (varchar 500, nullable), `is_active` (boolean, default true), `timestamps`
    - Add unique index on `slug`
    - _Requirements: 1.1, 1.5_

  - [x] 1.2 Create migration to add `request_type_id` and `service_request_id` columns to `service_requests` table
    - Add nullable `request_type_id` column (bigint unsigned) after `company_id`
    - Add nullable `service_request_id` column (bigint unsigned, self-referencing FK) after `request_type_id`
    - Add foreign key constraint `request_type_id` → `request_types.id`
    - Add foreign key constraint `service_request_id` → `service_requests.id` with `ON DELETE SET NULL`
    - _Requirements: 2.2, 3.1, 8.5_

  - [x] 1.3 Create `meeting_details` table migration
    - Define columns: `id`, `service_request_id` (unique FK), `scheduled_date` (date), `start_time` (time), `expected_duration_minutes` (unsigned int), `location` (varchar 255, nullable), `virtual_meeting_url` (varchar 2048, nullable), `timestamps`
    - Add unique index on `service_request_id`
    - Add FK constraint to `service_requests.id` with `ON DELETE CASCADE`
    - _Requirements: 4.3_

  - [x] 1.4 Create `meeting_participants` table migration
    - Define columns: `id`, `meeting_detail_id` (FK), `name` (varchar 255), `email` (varchar 255), `role` (enum: organizador, participante, invitado, default participante), `user_id` (nullable FK → users.id, ON DELETE SET NULL), `attended` (boolean, nullable), `timestamps`
    - Add unique composite index on `(meeting_detail_id, email)`
    - Add FK constraint to `meeting_details.id` with `ON DELETE CASCADE`
    - _Requirements: 5.2_

  - [x] 1.5 Create `service_request_assignment_history` table migration
    - Define columns: `id`, `service_request_id` (FK), `previous_assignee_id` (nullable FK → users.id, ON DELETE SET NULL), `new_assignee_id` (FK → users.id, ON DELETE SET NULL), `reason` (text), `changed_by` (FK → users.id, ON DELETE SET NULL), `timestamps`
    - Add index on `service_request_id` and `created_at`
    - Add FK constraint to `service_requests.id` with `ON DELETE CASCADE`
    - _Requirements: 11.2_

  - [x] 1.6 Create `RequestTypeSeeder` with default types
    - Seed `request_types` table with: general, reunion, compromiso, seguimiento, solicitud_documental
    - Include slug, name, description, and is_active for each
    - Register seeder in `DatabaseSeeder`
    - _Requirements: 1.3_

- [ ] 2. Checkpoint - Run migrations and verify schema
  - Ensure all migrations run without errors, ask the user if questions arise.

- [x] 3. Models and relationships
  - [x] 3.1 Create `RequestType` model
    - Create `app/Models/RequestType.php`
    - Define fillable attributes: slug, name, description, is_active
    - Define casts: `is_active` as boolean
    - Add scopes: `scopeActive($query)`, `scopeBySlug($query, $slug)`
    - Add relationship: `serviceRequests()` hasMany ServiceRequest
    - _Requirements: 1.1, 1.4, 1.7_

  - [x] 3.2 Create `MeetingDetail` model
    - Create `app/Models/MeetingDetail.php`
    - Define fillable attributes: service_request_id, scheduled_date, start_time, expected_duration_minutes, location, virtual_meeting_url
    - Define casts: `scheduled_date` as date, `start_time` as string
    - Add accessor: `getEndTimeAttribute()` computed from start_time + duration
    - Add relationships: `serviceRequest()` belongsTo, `participants()` hasMany MeetingParticipant
    - _Requirements: 4.3_

  - [x] 3.3 Create `MeetingParticipant` model
    - Create `app/Models/MeetingParticipant.php`
    - Define fillable attributes: meeting_detail_id, name, email, role, user_id, attended
    - Define casts: `attended` as boolean, `role` as string
    - Add scopes: `scopeOrganizers($query)`, `scopeAttended($query)`
    - Add relationships: `meetingDetail()` belongsTo, `user()` belongsTo User (nullable)
    - _Requirements: 5.2_

  - [x] 3.4 Create `ServiceRequestAssignmentHistory` model
    - Create `app/Models/ServiceRequestAssignmentHistory.php`
    - Define fillable attributes: service_request_id, previous_assignee_id, new_assignee_id, reason, changed_by
    - Add relationships: `serviceRequest()` belongsTo, `previousAssignee()` belongsTo User, `newAssignee()` belongsTo User, `changedBy()` belongsTo User
    - _Requirements: 11.2_

  - [x] 3.5 Add new relationships and attributes to existing `ServiceRequest` model
    - Add `request_type_id` and `service_request_id` to fillable
    - Add relationship: `requestType()` belongsTo RequestType
    - Add relationship: `meetingDetail()` hasOne MeetingDetail
    - Add relationship: `assignmentHistories()` hasMany ServiceRequestAssignmentHistory
    - Add relationship: `parentRequest()` belongsTo ServiceRequest (via service_request_id)
    - Add relationship: `childRequests()` hasMany ServiceRequest (via service_request_id)
    - _Requirements: 2.2, 8.1, 8.5, 11.2_

- [x] 4. Validation layer (strategy pattern)
  - [x] 4.1 Create `RequestTypeValidatorInterface`
    - Create `app/Contracts/RequestTypeValidatorInterface.php`
    - Define methods: `validateTransition(ServiceRequest $sr, string $from, string $to): ValidationResult`, `getRequiredFieldsForCreation(): array`
    - Create `app/DTOs/ValidationResult.php` with `passed`, `errors` properties
    - _Requirements: 10.5_

  - [x] 4.2 Create `GeneralTypeValidator` (no-op implementation)
    - Create `app/Services/Validators/GeneralTypeValidator.php`
    - Implement `RequestTypeValidatorInterface`
    - `validateTransition()` always returns passed=true (no extra validations)
    - `getRequiredFieldsForCreation()` returns empty array
    - _Requirements: 2.3, 3.2, 5 (Property 5)_

  - [x] 4.3 Create `MeetingTypeValidator`
    - Create `app/Services/Validators/MeetingTypeValidator.php`
    - Implement `RequestTypeValidatorInterface`
    - `validateTransition()` logic:
      - PENDIENTE → ACEPTADA: verify at least one participant with role "organizador"
      - EN_PROCESO → RESUELTA: verify at least one evidence record (ARCHIVO, ACTA, or ENLACE)
      - RESUELTA → CERRADA: verify at least one participant has `attended = true` AND at least one participant exists
    - `getRequiredFieldsForCreation()`: returns scheduled_date, start_time, expected_duration_minutes
    - Return specific error messages in Spanish per design error table
    - _Requirements: 5.4, 6.3, 10.1, 10.2, 10.3, 10.5, 10.6_

  - [x] 4.4 Integrate `RequestTypeValidator` into `ServiceRequestWorkflow` trait
    - Modify the existing workflow trait to resolve the appropriate validator based on the service request's type
    - Before executing a state transition, call `validateTransition()` on the resolved validator
    - If validation fails, throw exception with the error message and preserve current state
    - For null type or "general" type, use `GeneralTypeValidator` (no-op)
    - _Requirements: 3.2, 10.3, 10.5_

  - [ ]* 4.5 Write property test for null-type bypass (Property 5)
    - **Property 5: Null-type requests bypass type-specific validations**
    - **Validates: Requirements 2.3, 3.2**

  - [ ]* 4.6 Write property test for state preservation on failed validation (Property 15)
    - **Property 15: State preservation on failed type-specific validation**
    - **Validates: Requirements 10.5**

- [x] 5. Service layer - MeetingLifecycleService
  - [x] 5.1 Create `MeetingLifecycleService`
    - Create `app/Services/MeetingLifecycleService.php`
    - Implement `createMeetingDetails(ServiceRequest $sr, array $data): MeetingDetail`
    - Implement `updateMeetingDetails(MeetingDetail $md, array $data): MeetingDetail` — only if request status is PENDIENTE
    - Implement `addParticipant(MeetingDetail $md, array $data): MeetingParticipant` — auto-link user_id if email matches existing user
    - Implement `removeParticipant(MeetingParticipant $mp): void`
    - Implement `markAttendance(MeetingParticipant $mp, bool $attended): void`
    - Implement `getCommitments(ServiceRequest $sr): Collection` — tasks with type='impact'
    - Implement `createCommitment(ServiceRequest $sr, array $data): Task` — creates task with type='impact' and service_request_id
    - _Requirements: 4.1, 4.4, 4.6, 5.1, 5.3, 5.5, 7.1, 7.3_

  - [ ]* 5.2 Write property test for meeting scheduling validation (Property 7)
    - **Property 7: Meeting scheduling field validation**
    - **Validates: Requirements 4.1, 4.5**

  - [ ]* 5.3 Write property test for meeting details editability by status (Property 8)
    - **Property 8: Meeting details editability by status**
    - **Validates: Requirements 4.4, 4.6, 10.4**

  - [ ]* 5.4 Write property test for participant email uniqueness (Property 9)
    - **Property 9: Participant email uniqueness within a meeting**
    - **Validates: Requirements 5.6**

  - [ ]* 5.5 Write property test for organizador requirement (Property 10)
    - **Property 10: Organizador required for meeting transition from PENDIENTE**
    - **Validates: Requirements 5.4**

  - [ ]* 5.6 Write property test for participant user auto-linking (Property 11)
    - **Property 11: Participant user auto-linking**
    - **Validates: Requirements 5.5**

  - [ ]* 5.7 Write property test for meeting evidence required for resolution (Property 12)
    - **Property 12: Meeting evidence required for resolution**
    - **Validates: Requirements 6.3, 10.1**

  - [ ]* 5.8 Write property test for attendance required for closure (Property 14)
    - **Property 14: Attendance required for meeting closure**
    - **Validates: Requirements 10.2, 10.6**

  - [ ]* 5.9 Write property test for commitment creates impact task (Property 16)
    - **Property 16: Commitment creates impact task with correct parent**
    - **Validates: Requirements 7.3**

  - [ ]* 5.10 Write property test for commitment field validation (Property 17)
    - **Property 17: Commitment field validation**
    - **Validates: Requirements 7.2**

- [x] 6. Service layer - AssignmentHistoryService
  - [x] 6.1 Create `AssignmentHistoryService`
    - Create `app/Services/AssignmentHistoryService.php`
    - Implement `recordAssignment(ServiceRequest $sr, ?int $previousId, int $newId, string $reason, int $changedBy): ServiceRequestAssignmentHistory`
      - Create assignment history record
      - Create system evidence record (evidence_type = 'SISTEMA') documenting the reassignment
    - Implement `getHistory(ServiceRequest $sr): Collection` — sorted by created_at ASC
    - Implement `transferTasks(ServiceRequest $sr, int $fromTechnicianId, int $toTechnicianId): int`
      - Transfer tasks with status in {pending, in_progress, blocked, in_review}
      - Leave tasks with status in {completed, cancelled, rescheduled} unchanged
    - Wrap reassignment logic in a DB transaction with `lockForUpdate()` on the service request
    - Validate status is in {PENDIENTE, ACEPTADA, EN_PROCESO, PAUSADA} before allowing reassignment
    - _Requirements: 11.1, 11.3, 11.4, 11.5, 11.6, 11.8_

  - [ ]* 6.2 Write property test for assignment history record completeness (Property 22)
    - **Property 22: Assignment history record completeness**
    - **Validates: Requirements 11.1**

  - [ ]* 6.3 Write property test for chronological ordering (Property 23)
    - **Property 23: Assignment history chronological ordering**
    - **Validates: Requirements 11.3**

  - [ ]* 6.4 Write property test for reassignment status guard (Property 24)
    - **Property 24: Reassignment status guard**
    - **Validates: Requirements 11.5**

  - [ ]* 6.5 Write property test for task transfer on reassignment (Property 25)
    - **Property 25: Task transfer on reassignment**
    - **Validates: Requirements 11.6, 11.8**

  - [ ]* 6.6 Write property test for reassignment reason validation (Property 26)
    - **Property 26: Reassignment reason length validation**
    - **Validates: Requirements 11.7**

- [x] 7. Service layer - TraceabilityChainService
  - [x] 7.1 Create `TraceabilityChainService`
    - Create `app/Services/TraceabilityChainService.php`
    - Implement `buildChain(ServiceRequest $sr, int $maxDepth = 3): array`
      - Use eager loading with constrained depth (3 levels of childRequests)
      - Return tree structure with nodes containing: ticket_number, title, status, type_label, assigned technician name (or "Sin asignar"), created_at
      - At max depth, include `child_requests_count` for truncation indicator
      - Limit to 50 children per level
    - Implement `getChainForView(ServiceRequest $sr): array`
      - Includes commitments (tasks with type='impact') as distinct nodes with type_label "compromiso"
      - Returns null if request has no parent and no children (chain section hidden)
    - Use N+1 prevention pattern from design (eager loading with select constraints)
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

  - [ ]* 7.2 Write property test for traceability chain depth limiting (Property 20)
    - **Property 20: Traceability chain depth limiting**
    - **Validates: Requirements 9.2, 9.3**

  - [ ]* 7.3 Write property test for chain node completeness (Property 21)
    - **Property 21: Traceability chain node completeness**
    - **Validates: Requirements 9.4**

- [ ] 8. Checkpoint - Ensure service layer tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Form requests (validation)
  - [x] 9.1 Modify `StoreServiceRequestRequest` to support request type
    - Add optional `request_type_id` validation: nullable, exists in request_types table, must reference active type
    - Add conditional meeting fields validation: when type slug = "reunion", require scheduled_date, start_time, expected_duration_minutes
    - Validate scheduled_date >= today, expected_duration_minutes between 5–480
    - Validate optional location (max 255 chars), virtual_meeting_url (max 2048 chars)
    - Validate `service_request_id` (parent FK): nullable, exists in service_requests, not soft-deleted
    - _Requirements: 2.1, 2.4, 2.5, 4.1, 4.5, 8.7_

  - [x] 9.2 Create `StoreMeetingParticipantRequest`
    - Create `app/Http/Requests/StoreMeetingParticipantRequest.php`
    - Validate name (required, max 255), email (required, valid email format), role (required, in: organizador, participante, invitado)
    - Validate email uniqueness within the meeting (custom rule checking meeting_participants table)
    - _Requirements: 5.1, 5.6_

  - [x] 9.3 Create `StoreCommitmentRequest`
    - Create `app/Http/Requests/StoreCommitmentRequest.php`
    - Validate title (required, 1–255 chars), description (required, 1–2000 chars), technician_id (required, exists in users), due_date (required, date >= today)
    - _Requirements: 7.2, 7.6_

  - [x] 9.4 Create `ReassignServiceRequestRequest`
    - Create `app/Http/Requests/ReassignServiceRequestRequest.php`
    - Validate new_assignee_id (required, exists in users), reason (required, string, min:10, max:500)
    - _Requirements: 11.7_

  - [ ]* 9.5 Write property test for slug format validation (Property 1)
    - **Property 1: Slug format validation**
    - **Validates: Requirements 1.1**

  - [ ]* 9.6 Write property test for slug uniqueness enforcement (Property 2)
    - **Property 2: Slug uniqueness enforcement**
    - **Validates: Requirements 1.6**

  - [ ]* 9.7 Write property test for inactive type blocks creation (Property 3)
    - **Property 3: Inactive type blocks new request creation**
    - **Validates: Requirements 1.4, 1.8, 2.5**

  - [ ]* 9.8 Write property test for type immutability (Property 4)
    - **Property 4: Type immutability after creation**
    - **Validates: Requirements 2.6**

  - [ ]* 9.9 Write property test for ACTA MIME type validation (Property 13)
    - **Property 13: ACTA MIME type validation**
    - **Validates: Requirements 6.5**

- [x] 10. Controllers
  - [x] 10.1 Extend `ServiceRequestController::store()` for type handling
    - Accept `request_type_id` from form input
    - When type is "reunion", delegate to `MeetingLifecycleService::createMeetingDetails()` after creating the service request
    - Insert meeting participants if provided during creation
    - Support `service_request_id` (parent FK) for derived request creation
    - Pre-populate company, requester, and service family from parent when `service_request_id` is present
    - _Requirements: 2.1, 2.4, 4.1, 8.1, 8.4_

  - [x] 10.2 Extend `ServiceRequestController::show()` for type-specific data
    - Load traceability chain via `TraceabilityChainService::getChainForView()`
    - Load assignment history via `AssignmentHistoryService::getHistory()`
    - Load meeting detail with participants when type is "reunion"
    - Load commitments (tasks with type='impact') for meeting requests
    - Pass `childRequests` limited to 50 entries with ticket_number and status
    - Show parent request link if `service_request_id` is not null
    - _Requirements: 8.2, 8.3, 9.1, 9.5, 11.3_

  - [x] 10.3 Enhance `ServiceRequestController::reassignSubmit()` for assignment history
    - Validate request using `ReassignServiceRequestRequest`
    - Check status guard (only PENDIENTE, ACEPTADA, EN_PROCESO, PAUSADA)
    - Call `AssignmentHistoryService::recordAssignment()`
    - Call `AssignmentHistoryService::transferTasks()` to move active tasks to new technician
    - _Requirements: 11.1, 11.4, 11.5, 11.6_

  - [x] 10.4 Create `MeetingRequestController`
    - Create `app/Http/Controllers/MeetingRequestController.php`
    - Implement `updateDetails(Request $request, ServiceRequest $sr)` — update meeting scheduling (only if PENDIENTE)
    - Implement `addParticipant(StoreMeetingParticipantRequest $request, ServiceRequest $sr)` — add participant via MeetingLifecycleService
    - Implement `removeParticipant(ServiceRequest $sr, MeetingParticipant $participant)` — remove participant
    - Implement `markAttendance(Request $request, ServiceRequest $sr)` — bulk mark attendance
    - Implement `storeCommitment(StoreCommitmentRequest $request, ServiceRequest $sr)` — create commitment via MeetingLifecycleService
    - _Requirements: 4.4, 5.1, 5.3, 7.1_

  - [x] 10.5 Register new routes for meeting and derived request operations
    - Add meeting sub-routes under service requests: `service-requests/{sr}/meeting/` (update-details, participants, attendance, commitments)
    - Add derived request creation route: `service-requests/{sr}/derive`
    - Place in `routes/features/` following existing route organization pattern
    - _Requirements: 4.4, 5.1, 7.1, 8.1_

  - [ ]* 10.6 Write property test for derived request parent FK (Property 18)
    - **Property 18: Derived request stores parent FK**
    - **Validates: Requirements 8.1**

  - [ ]* 10.7 Write property test for derived request form pre-population (Property 19)
    - **Property 19: Derived request form pre-population**
    - **Validates: Requirements 8.4**

  - [ ]* 10.8 Write property test for priority scoring independence (Property 6)
    - **Property 6: Priority scoring independence from request type**
    - **Validates: Requirements 3.5**

- [ ] 11. Checkpoint - Ensure controller tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Blade views - Type selector and meeting forms
  - [x] 12.1 Add request type selector to service request creation form
    - Modify `resources/views/service-requests/create.blade.php` (or relevant partial)
    - Add optional dropdown populated with active request types from Type_Registry
    - Use Alpine.js to show/hide meeting-specific fields when "reunion" type is selected
    - Pre-select no type by default (null = general workflow)
    - _Requirements: 2.1, 2.4_

  - [x] 12.2 Create meeting detail fields section (Blade partial)
    - Create `resources/views/service-requests/partials/_meeting-details.blade.php`
    - Fields: scheduled_date (date picker), start_time (time input), expected_duration_minutes (number input, 5–480), location (text, optional), virtual_meeting_url (url, optional)
    - Conditionally displayed via Alpine.js when type = "reunion"
    - Show inline validation errors
    - _Requirements: 2.4, 4.1, 4.2_

  - [x] 12.3 Create participant management section (Blade partial)
    - Create `resources/views/service-requests/partials/_meeting-participants.blade.php`
    - Form to add participant: name, email, role (select: organizador, participante, invitado)
    - List of current participants with remove button
    - Maximum 50 participants indication
    - Attendance checkboxes (shown only when status allows: RESUELTA or CERRADA)
    - _Requirements: 5.1, 5.3_

  - [x] 12.4 Create commitment management section (Blade partial)
    - Create `resources/views/service-requests/partials/_meeting-commitments.blade.php`
    - Form to create commitment: title, description, responsible technician (dropdown), due_date
    - List of existing commitments showing title, status, responsible, due date
    - Visual indicator when all commitments are completed (icon/label)
    - Only shown when status is EN_PROCESO, RESUELTA, or REABIERTO
    - _Requirements: 7.1, 7.2, 7.5_

- [x] 13. Blade views - Traceability and assignment history
  - [x] 13.1 Create traceability chain view component
    - Create `resources/views/service-requests/partials/_traceability-chain.blade.php`
    - Display hierarchical tree with parent-child relationships (max 3 levels depth)
    - Each node shows: ticket_number (linked), title, status badge, type label, assigned technician (or "Sin asignar"), created_at
    - Distinguish commitment nodes from derived request nodes (different icon/label)
    - Show truncation indicator with hidden children count at max depth
    - Only display section if request has parent or children
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

  - [x] 13.2 Create assignment history section
    - Create `resources/views/service-requests/partials/_assignment-history.blade.php`
    - Display chronological list (oldest first) of assignment changes
    - Each record shows: date/time, previous assignee (or "Sin asignar"), new assignee, reason, changed by user
    - Integrated into service request detail view
    - _Requirements: 11.3_

  - [x] 13.3 Create derived request creation form
    - Create `resources/views/service-requests/partials/_derive-request.blade.php`
    - Form pre-populated with parent's company, requester, and service family
    - Allow operator to override each pre-populated field
    - Show link back to parent request
    - _Requirements: 8.2, 8.3, 8.4_

  - [x] 13.4 Integrate new sections into service request detail view
    - Modify `resources/views/service-requests/show.blade.php`
    - Include `_meeting-details`, `_meeting-participants`, `_meeting-commitments` partials (conditional on type = "reunion")
    - Include `_traceability-chain` partial (conditional on has parent or children)
    - Include `_assignment-history` partial
    - Include derived requests list showing child ticket numbers and statuses (max 50)
    - Show parent request link when applicable
    - _Requirements: 8.2, 8.3, 9.1, 9.5, 11.3_

- [ ] 14. Checkpoint - Ensure views render correctly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. Workflow integration and evidence handling
  - [x] 15.1 Add ACTA evidence type validation
    - Extend evidence upload validation to support "ACTA" evidence_type for meeting requests
    - Validate MIME types for ACTA: application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document, image/jpeg, image/jpg, image/png
    - Use existing `service_request_evidences` table and file storage — no schema changes
    - _Requirements: 6.1, 6.2, 6.4, 6.5_

  - [x] 15.2 Add type immutability guard to `ServiceRequest` model
    - Override `setAttribute` or use model event to prevent `request_type_id` changes after initial creation
    - Throw validation exception with error message when change is attempted
    - _Requirements: 2.6_

  - [x] 15.3 Add "general" type deactivation guard
    - In `RequestType` model or service layer, prevent deactivation of the type with slug "general"
    - Return error message: "No se puede desactivar el tipo 'general'."
    - _Requirements: 1.7_

  - [x] 15.4 Ensure backward compatibility for reporting and Smart Parser
    - Verify existing report queries include null-type and "general" type requests in same result sets
    - Verify Smart Request Parser continues producing null `request_type_id` by default
    - Verify priority scoring, SLA, and criticality calculations do not reference `request_type_id`
    - _Requirements: 3.3, 3.4, 3.5_

- [ ] 16. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document (26 properties total)
- Unit tests validate specific examples and edge cases
- The project uses Laravel 12 with PHP 8.2+, Blade + Alpine.js + Tailwind CSS, and PHPUnit
- All migrations are additive — no existing table columns are modified or removed
- The `ServiceRequestWorkflow` trait modification uses a hook pattern to avoid altering existing state machine logic
- Existing service requests with null `request_type_id` continue working identically (backward compatibility)
- The `phpquickcheck` library is used for property-based testing

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.5", "1.6"] },
    { "id": 2, "tasks": ["1.4"] },
    { "id": 3, "tasks": ["3.1", "3.2", "3.3", "3.4", "3.5"] },
    { "id": 4, "tasks": ["4.1"] },
    { "id": 5, "tasks": ["4.2", "4.3"] },
    { "id": 6, "tasks": ["4.4", "4.5", "4.6"] },
    { "id": 7, "tasks": ["5.1", "6.1", "7.1"] },
    { "id": 8, "tasks": ["5.2", "5.3", "5.4", "5.5", "5.6", "5.7", "5.8", "5.9", "5.10", "6.2", "6.3", "6.4", "6.5", "6.6", "7.2", "7.3"] },
    { "id": 9, "tasks": ["9.1", "9.2", "9.3", "9.4"] },
    { "id": 10, "tasks": ["9.5", "9.6", "9.7", "9.8", "9.9"] },
    { "id": 11, "tasks": ["10.1", "10.2", "10.3", "10.4", "10.5"] },
    { "id": 12, "tasks": ["10.6", "10.7", "10.8"] },
    { "id": 13, "tasks": ["12.1", "12.2", "12.3", "12.4"] },
    { "id": 14, "tasks": ["13.1", "13.2", "13.3", "13.4"] },
    { "id": 15, "tasks": ["15.1", "15.2", "15.3", "15.4"] }
  ]
}
```
