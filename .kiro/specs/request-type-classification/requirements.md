# Requirements Document

## Introduction

This feature evolves the SAPP service request management system to support differentiated request types, each with its own lifecycle behavior. Phase 1 focuses on introducing a request type classification system, implementing the Meeting lifecycle (the primary use case), activating the parent-child derived request traceability, managing commitments through the existing task system, and enhancing assignment/reassignment traceability with a dedicated history log. The implementation preserves full backward compatibility with existing service requests and does not alter the current reporting module or Smart Request Parser pipeline.

## Glossary

- **Service_Request**: The core entity in SAPP representing a service request tracked through a state machine workflow.
- **Request_Type**: A classification attribute on a Service_Request that determines its lifecycle behavior, required fields, and evidence requirements.
- **Meeting_Request**: A Service_Request of type "reunion" that follows the meeting lifecycle workflow (scheduling, attendees, evidence, minutes, commitments, derived requests).
- **Commitment**: An actionable item arising from a meeting, represented as a Task linked to the originating Meeting_Request, with a responsible person and deadline.
- **Derived_Request**: A new Service_Request created from a parent request (e.g., a commitment generating a follow-up task request), linked via the parent-child relationship.
- **Parent_Request**: A Service_Request that originates one or more Derived_Requests, forming a traceability chain.
- **Meeting_Participant**: A record of an attendee in a Meeting_Request, including their role and attendance confirmation.
- **Meeting_Minutes**: A structured document attached to a Meeting_Request summarizing discussions, decisions, and commitments.
- **Type_Registry**: The configuration store that defines available Request_Types and their associated metadata.
- **Traceability_Chain**: The full lineage from a parent Service_Request through all its Derived_Requests, enabling tracking of origin to outcome.
- **Legacy_Request**: An existing Service_Request created before the type classification system, which continues operating under the default workflow without modification.
- **Assignment_History**: A chronological log of all assignment and reassignment events on a Service_Request, recording who held responsibility at each stage.

## Requirements

### Requirement 1: Request Type Registry

**User Story:** As a system administrator, I want to define and manage request types in a central registry, so that each type can have distinct lifecycle behavior.

#### Acceptance Criteria

1. THE Type_Registry SHALL store each Request_Type with a unique slug (1–50 lowercase alphanumeric or underscore characters), a display name (1–100 characters), a description (0–500 characters), and an active status (boolean, defaulting to true).
2. WHEN a new Service_Request is created without specifying a Request_Type, THE Service_Request SHALL default to type "general" and follow the existing workflow unchanged.
3. THE Type_Registry SHALL include at minimum the following types: "general", "reunion" (meeting), "compromiso" (commitment), "seguimiento" (follow-up), and "solicitud_documental" (document request).
4. IF a Request_Type is deactivated, THEN THE Type_Registry SHALL prevent creation of new Service_Requests with that type while preserving existing requests of that type unchanged.
5. THE Type_Registry SHALL store the type configuration in a dedicated `request_types` database table, separate from the `service_requests` table.
6. IF a user attempts to create a Request_Type with a slug that already exists, THEN THE Type_Registry SHALL reject the operation and return an error message indicating the slug is already in use.
7. THE Type_Registry SHALL prevent deactivation of the "general" Request_Type.
8. IF a user attempts to create a Service_Request with a Request_Type whose active status is false, THEN THE System SHALL reject the creation and return an error message indicating the selected type is not available.

### Requirement 2: Request Type Assignment

**User Story:** As a service request operator, I want to assign a type when creating a service request, so that the system applies the appropriate lifecycle behavior.

#### Acceptance Criteria

1. WHEN creating a new Service_Request, THE Service_Request creation form SHALL display an optional Request_Type selector populated from the active entries in the Type_Registry.
2. THE Service_Request model SHALL store the selected type as a nullable `request_type_id` foreign key column in the `service_requests` table.
3. IF a Service_Request has a null `request_type_id`, THEN THE Service_Request SHALL follow the same state transitions, validations, and workflow rules as a Legacy_Request without requiring any type-specific fields or evidence.
4. WHEN a Service_Request is assigned type "reunion", THE Service_Request creation form SHALL display additional meeting-specific fields (scheduled date, start time, location, expected duration in minutes).
5. IF the specified `request_type_id` does not exist in the Type_Registry or references an inactive entry, THEN THE Service_Request creation process SHALL reject the request with a validation error indicating the selected type is not available.
6. WHEN a Service_Request has been created with a `request_type_id`, THE Service_Request SHALL not allow changing its assigned type after creation.

### Requirement 3: Backward Compatibility

**User Story:** As a system operator, I want existing service requests to continue functioning without modification, so that ongoing operations are not disrupted.

#### Acceptance Criteria

1. THE Service_Request model SHALL add the `request_type_id` column as a nullable foreign key with no DEFAULT value, so that all existing rows retain a NULL value in that column without requiring a data backfill migration.
2. WHILE a Service_Request has a null `request_type_id`, THE Service_Request workflow SHALL skip all type-specific validations (meeting evidence requirements, attendance checks, scheduling field requirements) and execute only the standard state transitions defined prior to the type classification system.
3. THE existing reporting module (7 unified reports) SHALL include Service_Requests with a null `request_type_id` and Service_Requests with type "general" in the same result sets without applying any type-based filtering or grouping.
4. THE Smart_Request_Parser pipeline SHALL continue operating without modification, as parsed requests receive a null `request_type_id` by default.
5. THE existing entry channels, SLA assignments, criticality levels, and priority scoring calculations SHALL execute without referencing the `request_type_id` column, producing identical results for all Service_Requests regardless of their assigned type.
6. WHEN a Legacy_Request (null `request_type_id`) is subsequently assigned a Request_Type, THE Service_Request SHALL begin enforcing the type-specific validations only on future state transitions and SHALL NOT retroactively validate prior states.

### Requirement 4: Meeting Scheduling

**User Story:** As a service request operator, I want to schedule meetings with date, time, location, and expected duration, so that participants know when and where to attend.

#### Acceptance Criteria

1. WHEN a Meeting_Request is created, THE Meeting_Request SHALL require a scheduled date (not in the past relative to the current date), a start time, and an expected duration in minutes (minimum 5, maximum 480).
2. WHEN a Meeting_Request is created, THE Meeting_Request SHALL accept an optional location field (text, up to 255 characters) and an optional virtual meeting URL (up to 2048 characters).
3. THE Meeting_Request scheduling data SHALL be stored in a dedicated `meeting_details` table linked to the Service_Request via foreign key.
4. WHILE a Meeting_Request is in status PENDIENTE, THE Meeting_Request scheduling details (scheduled date, start time, duration, location, virtual meeting URL) SHALL remain editable.
5. IF a Meeting_Request is created without required scheduling fields (date, start time, duration), THEN THE Service_Request creation process SHALL reject the request with a validation error listing the missing fields.
6. IF a user attempts to edit the scheduling details of a Meeting_Request that is not in status PENDIENTE, THEN THE system SHALL reject the modification with a validation error indicating that scheduling changes are only permitted while the request is in PENDIENTE status.

### Requirement 5: Meeting Participants

**User Story:** As a service request operator, I want to register participants for a meeting, so that attendance can be tracked and commitments attributed to specific people.

#### Acceptance Criteria

1. WHEN a Meeting_Request is created or edited, THE Meeting_Request SHALL accept a list of participants (maximum 50) where each participant has a name (maximum 255 characters), a valid email address, and a role (one of: organizador, participante, invitado).
2. THE Meeting_Participant records SHALL be stored in a dedicated `meeting_participants` table with a foreign key to the `meeting_details` record.
3. WHEN a Meeting_Request transitions to status RESUELTA or CERRADA, THE Meeting_Request SHALL allow marking each participant as attended (boolean).
4. IF a Meeting_Request attempts to transition from PENDIENTE without at least one participant with role "organizador" registered, THEN THE workflow SHALL reject the transition with an error message indicating that an organizador is required.
5. IF a participant is registered with an email matching an existing User in the system, THEN THE Meeting_Participant record SHALL store the corresponding `user_id` for internal traceability.
6. IF a participant is registered with an email address that already exists in the participant list for the same Meeting_Request, THEN THE Meeting_Request SHALL reject the duplicate entry with a validation error indicating the email is already registered.

### Requirement 6: Meeting Evidence and Documents

**User Story:** As a service request operator, I want to attach evidence that a meeting took place and upload related documents, so that there is a verifiable record.

#### Acceptance Criteria

1. WHILE a Meeting_Request is in status EN_PROCESO, RESUELTA, or CERRADA, THE Evidence system SHALL accept uploads of type ARCHIVO for meeting documents (minutes, photos, recordings), following existing file validation rules (maximum 10 files per upload, maximum 10 MB per file).
2. THE Evidence system SHALL support a new evidence category "ACTA" (minutes) specific to Meeting_Requests, stored using the existing `evidence_type` field.
3. WHEN a Meeting_Request transitions to status RESUELTA, THE Meeting_Request SHALL require at least one evidence record of type ARCHIVO, ACTA, or ENLACE to be present; IF no qualifying evidence record exists, THEN THE system SHALL prevent the transition and display an error message indicating that at least one evidence attachment is required.
4. THE Evidence uploads for Meeting_Requests SHALL use the existing `service_request_evidences` table and existing file storage mechanisms without schema changes to that table.
5. WHEN evidence of type "ACTA" is uploaded, THE Evidence system SHALL validate that the file has a MIME type corresponding to PDF, DOCX, JPG, JPEG, or PNG; IF the file does not match an accepted format, THEN THE Evidence system SHALL reject the upload and display an error message indicating the accepted formats.

### Requirement 7: Commitment Management

**User Story:** As a service request operator, I want to register commitments from a meeting with responsible persons and deadlines, so that action items are tracked to completion.

#### Acceptance Criteria

1. WHILE a Meeting_Request is in status EN_PROCESO, RESUELTA, or REABIERTO, THE Commitment management interface SHALL allow creating commitments as Tasks linked to the Meeting_Request.
2. THE Commitment (Task) SHALL require a title (1 to 255 characters), description (1 to 2000 characters), responsible person (technician assignment), and a due date that is equal to or later than the current date.
3. WHEN a Commitment is created from a Meeting_Request, THE Task SHALL be created with type "impact" and a reference to the originating Service_Request via `service_request_id`.
4. THE Commitment Task SHALL follow the existing Task lifecycle (pending → in_progress → completed) including the intermediate states (blocked, in_review) and terminal states (cancelled, rescheduled) without modification to the Task model.
5. WHEN all Commitments (Tasks) of type "impact" linked to a Meeting_Request reach status "completed", THE Meeting_Request detail view SHALL display a persistent visual indicator (a distinct icon or label in the commitments section) that all commitments are fulfilled but SHALL NOT auto-transition the Meeting_Request status.
6. IF a Commitment creation request fails validation (missing required fields or invalid due date), THEN THE Commitment management interface SHALL reject the creation and display a validation error message indicating which fields are invalid.

### Requirement 8: Derived Requests and Traceability

**User Story:** As a service request operator, I want to create derived requests from a parent request and trace the full lineage, so that I can track how one request leads to others.

#### Acceptance Criteria

1. WHEN a user creates a Derived_Request from an existing Service_Request, THE Derived_Request SHALL store the parent's ID in the `service_request_id` column of the `service_requests` table.
2. WHEN viewing a Service_Request that has one or more Derived_Requests, THE Service_Request detail view SHALL display a list of all Derived_Requests showing each child's ticket number and current status, limited to a maximum of 50 entries.
3. WHEN viewing a Derived_Request, THE Service_Request detail view SHALL display a navigable link to the Parent_Request showing the parent's ticket number and title.
4. WHEN creating a Derived_Request from a Parent_Request, THE Derived_Request creation form SHALL pre-populate the company, requester, and service family fields with the Parent_Request's values, while allowing the operator to override each field individually before submission.
5. THE `service_request_id` column SHALL be added to the `service_requests` table as a nullable self-referencing foreign key via a new migration.
6. WHEN a Service_Request with entry_channel "reunion" has associated tasks, THE operator SHALL be able to create a Derived_Request from that Service_Request, storing a reference to the originating task ID in the `evidence_data` JSON field of the first evidence record attached to the Derived_Request.
7. IF a user attempts to create a Derived_Request referencing a non-existent or soft-deleted parent Service_Request, THEN THE System SHALL reject the creation and display an error message indicating the parent request is invalid.
8. IF a Derived_Request's parent is deleted (soft-deleted), THEN THE System SHALL preserve the Derived_Request and display the parent link as unavailable rather than removing the Derived_Request.

### Requirement 9: Traceability Chain Visualization

**User Story:** As a manager, I want to visualize the full traceability chain from a meeting through its minutes, commitments, and derived requests, so that I can assess progress and accountability.

#### Acceptance Criteria

1. WHEN viewing a Meeting_Request detail, THE Traceability_Chain view SHALL display: the meeting scheduling summary (date, start time, duration, location), list of commitments (tasks) with their title and current status, and any Derived_Requests created from the meeting with their ticket number and status.
2. THE Traceability_Chain view SHALL display the chain as a hierarchical tree showing parent-child relationships starting from the root Service_Request (level 1) through a maximum depth of 3 child levels below the root.
3. WHEN a Derived_Request has its own Derived_Requests beyond the maximum depth of 3 child levels, THE Traceability_Chain view SHALL display a truncation indicator on the deepest visible node showing the count of hidden children.
4. THE Traceability_Chain view SHALL show for each node: ticket number, title, current status, node type label (reunion, compromiso, solicitud, or general), assigned technician name (or "Sin asignar" if no technician is assigned), and creation date.
5. IF a Service_Request has no parent and no children, THEN THE Traceability_Chain section SHALL not be displayed on the detail view.
6. THE Traceability_Chain view SHALL visually distinguish Commitment nodes (Tasks) from Derived_Request nodes using a different node type label and icon so that the manager can identify the nature of each item in the tree.

### Requirement 10: Meeting Lifecycle State Constraints

**User Story:** As a system administrator, I want meeting-specific validations on state transitions, so that meetings follow their complete lifecycle before being closed.

#### Acceptance Criteria

1. WHEN a Meeting_Request transitions from EN_PROCESO to RESUELTA, THE workflow validation SHALL verify that at least one ServiceRequestEvidence record of any evidence_type exists for the meeting before allowing the transition.
2. WHEN a Meeting_Request transitions from RESUELTA to CERRADA, THE workflow validation SHALL verify that attendance has been marked for at least one participant in the `meeting_participants` table with `attended = true`.
3. THE Meeting_Request SHALL follow the same state machine transitions defined for a standard ServiceRequest (PENDIENTE → ACEPTADA → EN_PROCESO → RESUELTA → CERRADA, including PAUSADA, CANCELADA, and REABIERTO branches) with the additional meeting-specific validations applied only on the EN_PROCESO → RESUELTA and RESUELTA → CERRADA transitions.
4. WHILE a Meeting_Request is in status PENDIENTE or ACEPTADA, THE Meeting_Request SHALL allow modification of the meeting date, start time, end time, location, and participant list without requiring additional validation beyond standard field-level constraints.
5. IF the Meeting_Request-specific validations fail during a state transition, THEN THE workflow SHALL block the transition, preserve the current state unchanged, and return an error message indicating which specific meeting requirement is unmet (missing evidence or missing attendance).
6. IF a Meeting_Request has no participants assigned when a user attempts the transition from RESUELTA to CERRADA, THEN THE workflow SHALL block the transition and return an error message indicating that at least one participant must be assigned to the meeting before closure.

### Requirement 11: Assignment History and Reassignment Traceability

**User Story:** As a service request operator, I want to view the full assignment history of a service request and ensure every reassignment is traceable with reasons and timestamps, so that accountability is maintained throughout the lifecycle.

#### Acceptance Criteria

1. WHEN a Service_Request is assigned or reassigned to a technician, THE system SHALL create an assignment history record storing: previous assignee (null for initial assignment), new assignee, reason for change, timestamp, and the user who performed the change.
2. THE system SHALL store assignment history records in a dedicated `service_request_assignment_history` table with a foreign key referencing the Service_Request.
3. WHEN viewing a Service_Request detail, THE system SHALL display the assignment history section with all assignment changes sorted in ascending chronological order (oldest first), showing for each record: date and time, previous assignee name (or "Sin asignar" when null), new assignee name, reason, and the user who made the change.
4. WHEN a Service_Request is reassigned, THE system SHALL create a system evidence record (evidence_type = 'SISTEMA') documenting the reassignment with previous assignee, new assignee, and reason.
5. IF a Service_Request is not in status PENDIENTE, ACEPTADA, EN_PROCESO, or PAUSADA, THEN THE system SHALL reject the reassignment action and display an error message indicating that reassignment is not allowed in the current status.
6. WHEN a Service_Request is reassigned, THE system SHALL transfer all tasks with status 'pending', 'in_progress', 'blocked', or 'in_review' from the previous technician to the new technician, leaving tasks with status 'completed', 'cancelled', or 'rescheduled' unchanged.
7. WHEN the reassignment form is submitted, THE system SHALL require a reason field containing between 10 and 500 characters; IF the reason is shorter than 10 characters or longer than 500 characters, THEN THE system SHALL reject the submission and display a validation error message indicating the character length constraint.
8. WHEN a Service_Request is reassigned and no tasks with status 'pending', 'in_progress', 'blocked', or 'in_review' exist for the previous technician, THE system SHALL complete the reassignment without transferring any tasks.
