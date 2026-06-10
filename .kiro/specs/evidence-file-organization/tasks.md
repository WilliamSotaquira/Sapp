# Implementation Plan: Evidence File Organization

## Overview

This plan implements a system for organizing evidence files into structured folders by cut number. The approach starts with database schema changes and models, then builds core services (EvidenceOrganizerService, DateSuggestionService), followed by controllers/routes, Blade views, and finally integration wiring. Each step builds incrementally on the previous one.

## Tasks

- [x] 1. Database migrations and models
  - [x] 1.1 Create migration for `system_settings` table and SystemSetting model
    - Create migration with columns: id, key (varchar 100, unique), value (text, nullable), timestamps
    - Insert initial row: key='evidence_base_path', value=NULL
    - Create `App\Models\SystemSetting` model with `$fillable = ['key', 'value']` and static `get()`/`set()` helpers
    - _Requirements: 1.2, 1.5_

  - [x] 1.2 Create migration for `evidence_organization_logs` table and EvidenceOrganizationLog model
    - Create migration with columns: id, evidence_id (FK), cut_id (FK), user_id (FK nullable), source_path, destination_path, result (enum: success/failed/skipped), error_message (nullable), created_at
    - Add composite index on (cut_id, created_at)
    - Create `App\Models\EvidenceOrganizationLog` model with relationships to Evidence, Cut, User
    - _Requirements: 7.6_

  - [x] 1.3 Create migration to add `folder_path` column to `cuts` table and update Cut model
    - Add nullable varchar(500) `folder_path` column to cuts table
    - Add `folder_path` to Cut model's `$fillable` array
    - Add `hasFolder(): bool` helper method to Cut model
    - _Requirements: 2.3, 2.7_

- [x] 2. Implement core DTOs
  - [x] 2.1 Create OrganizationResult, DateSuggestion, and OverlapResult DTOs
    - Create `App\DTOs\OrganizationResult` with properties: succeeded (array), failed (array), successCount, failureCount
    - Create `App\DTOs\DateSuggestion` with properties: startDate (Carbon), endDate (Carbon), format
    - Create `App\DTOs\OverlapResult` with properties: hasOverlap (bool), conflictingCut (?Cut)
    - _Requirements: 3.5, 5.1, 5.5_

- [x] 3. Implement EvidenceOrganizerService
  - [x] 3.1 Implement `resolveBasePath()` and `validateFolderName()` methods
    - `resolveBasePath()`: reads from SystemSetting, returns default `storage/app/public/evidences/cortes` when null/empty
    - `validateFolderName()`: validates regex `^[a-zA-Z0-9_-]+$`, length 1-128, uniqueness within base path
    - _Requirements: 1.2, 1.3, 2.4_

  - [x] 3.2 Write property tests for path resolution and folder name validation
    - **Property 1: Default path resolution** - Null/empty/whitespace settings return default path
    - **Property 2: Path validation rejects invalid characters** - Only valid patterns accepted
    - **Property 5: Custom folder name validation** - Regex, length, uniqueness checks
    - **Validates: Requirements 1.2, 1.3, 2.4**

  - [x] 3.3 Implement `suggestFolderPath()` and `createCutFolder()` methods
    - `suggestFolderPath(cutId, startDate)`: returns `{basePath}/corte-{cut_id}-{YYYY-MM-DD}`
    - `createCutFolder(folderPath)`: creates directory recursively, returns bool
    - _Requirements: 2.1, 2.3, 2.5_

  - [x] 3.4 Write property tests for folder path suggestion
    - **Property 4: Suggested folder path follows naming pattern** - Output matches `{basePath}/corte-{cut_id}-{YYYY-MM-DD}`
    - **Validates: Requirements 2.1**

  - [x] 3.5 Implement `organizeEvidences()` method - core file move logic
    - Implement per-file loop: SELECT FOR UPDATE → backup → copy → verify size → update DB → cleanup
    - Handle ENLACE-type evidence (skip filesystem, register URL only)
    - Place files in ticket-number subdirectory within Cut_Folder
    - Handle duplicate filenames with numeric suffix
    - Create audit log entry for each operation (success/failed/skipped)
    - Return OrganizationResult with succeeded/failed arrays
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 4.1, 4.2, 4.3, 4.5, 4.6, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 8.1, 8.2, 8.3_

  - [x] 3.6 Write property tests for organize evidences logic
    - **Property 6: Evidence placed in ticket-number subdirectory** - Destination contains sanitized ticket number
    - **Property 7: Filename preservation during move** - Filename unchanged when no conflict
    - **Property 8: ENLACE-type evidence skips filesystem operations** - No file move for URL evidences
    - **Property 9: Independent batch processing** - success + failed = total, failure doesn't block next
    - **Property 10: Duplicate filename gets numeric suffix** - Suffix appended, original unchanged
    - **Property 11: File integrity verification (size match)** - Destination size equals source size
    - **Property 12: Transactional atomicity on failure** - file_path unchanged on failure, file restored
    - **Property 13: Pre-move validation rejects invalid records** - Soft-deleted/missing records skipped
    - **Validates: Requirements 2.8, 3.3, 3.4, 3.5, 3.7, 4.2, 4.5, 7.2, 7.3, 8.2, 8.3**

  - [x] 3.7 Implement `cleanOrphanedBackups()` method
    - Find backup files older than 24 hours in the `_backups` directory
    - Delete orphaned backups and return count of cleaned files
    - _Requirements: 4.8_

- [x] 4. Implement DateSuggestionService
  - [x] 4.1 Implement `suggestDates()` method
    - Query most recent cut for the contract, suggest start_date = previous end_date + 1 day at 00:00
    - If no previous cut, suggest current date/time truncated to minute
    - Default end_date = last day of month containing start_date at 23:59
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [x] 4.2 Implement `validateNoOverlap()` method
    - Check overlap condition: A_start ≤ B_end AND B_start ≤ A_end for same contract
    - Return OverlapResult with conflicting cut details if overlap found
    - Accept optional excludeCutId for edit scenarios
    - _Requirements: 5.5, 5.6_

  - [x] 4.3 Write property tests for date suggestion and overlap detection
    - **Property 14: Date suggestion from previous cut** - start_date = prev end_date + 1 day at 00:00
    - **Property 15: End-date defaults to last day of month** - end_date is last day of month at 23:59
    - **Property 16: Overlap detection correctness** - Overlap iff A_start ≤ B_end AND B_start ≤ A_end
    - **Validates: Requirements 5.1, 5.2, 5.4, 5.5**

- [x] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Implement SystemSettingsController and routes
  - [x] 6.1 Create SystemSettingsController with `edit()` and `update()` methods
    - `edit()`: renders settings form with current base path value
    - `update()`: validates path (regex, exists, writable), persists via SystemSetting::set(), handles errors
    - Add admin role authorization check (deny non-admin with 403)
    - _Requirements: 1.1, 1.3, 1.4, 1.5, 1.6, 1.7_

  - [x] 6.2 Register routes for SystemSettingsController
    - `GET /settings` → edit (admin middleware)
    - `PUT /settings` → update (admin middleware)
    - _Requirements: 1.6_

  - [x] 6.3 Write unit tests for SystemSettingsController
    - Test settings form renders for admin
    - Test non-admin gets 403
    - Test invalid path rejected with error
    - Test valid path persisted correctly
    - Test persistence failure retains previous value
    - **Property 3: Settings persistence round-trip** - store and retrieve returns identical string
    - **Validates: Requirements 1.1, 1.3, 1.4, 1.5, 1.6, 1.7**

- [x] 7. Extend CutController for evidence organization
  - [x] 7.1 Add cut creation enhancements to CutController
    - Inject DateSuggestionService and EvidenceOrganizerService
    - Modify create form to show suggested folder path and dates
    - On store: validate folder name, create folder, store folder_path in Cut record
    - Handle no active contract scenario (show message, hide date fields)
    - Handle date overlap validation (show warning, disable submit)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [x] 7.2 Add `organizeEvidences()` action to CutController
    - Accept POST /cuts/{cut}/organize-evidences with evidence IDs (max 50)
    - Validate batch size, call EvidenceOrganizerService::organizeEvidences()
    - Return summary view with success/failure counts
    - _Requirements: 3.1, 3.5, 8.1, 8.4_

  - [x] 7.3 Register new routes for evidence organization
    - `POST /cuts/{cut}/organize-evidences` → organizeEvidences
    - _Requirements: 3.1, 8.1_

- [x] 8. Implement Blade views
  - [x] 8.1 Create settings view (`resources/views/settings/edit.blade.php`)
    - Text input for Base_Storage_Path with current value pre-filled
    - Validation error display
    - Submit button for admin users
    - _Requirements: 1.1, 1.4_

  - [x] 8.2 Update cut creation form to show folder path suggestion and date suggestion
    - Display suggested folder path with editable folder name segment
    - Display suggested start_date and end_date in YYYY-MM-DD HH:mm format
    - Show overlap warning with conflicting cut details when dates conflict
    - Show "no active contract" message when applicable
    - _Requirements: 2.1, 2.2, 5.1, 5.4, 5.6, 5.7_

  - [x] 8.3 Update cut list view to show `created_at` column
    - Add created_at column after start_date/end_date columns
    - Format as DD/MM/YYYY HH:mm
    - Display "Sin fecha de creación" for null values
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 8.4 Create evidence organization UI in cut detail view
    - Checkbox selection for evidence files (max 50)
    - "Organize" action button
    - Summary display after organization (success/failure counts)
    - Updated evidence count after operation
    - _Requirements: 3.1, 3.5, 8.1, 8.4_

  - [x] 8.5 Write unit tests for date format and created_at display
    - **Property 18: Date format output** - Non-null created_at renders as DD/MM/YYYY HH:mm
    - Test null created_at shows "Sin fecha de creación"
    - **Validates: Requirements 6.2, 6.3, 6.4**

- [x] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Audit log and cleanup integration
  - [x] 10.1 Wire audit log creation into organizeEvidences flow
    - Ensure every file operation (success/failed/skipped) creates an EvidenceOrganizationLog entry
    - Include source_path, destination_path, user_id, result, error_message
    - Handle audit log write failure gracefully (log to app log, don't block operation)
    - _Requirements: 7.6_

  - [x] 10.2 Register scheduled task for `cleanOrphanedBackups()`
    - Add Laravel scheduled command to run daily
    - Call EvidenceOrganizerService::cleanOrphanedBackups()
    - _Requirements: 4.8_

  - [x] 10.3 Write property test for audit log creation
    - **Property 17: Audit log creation for every operation** - Entry created for every operation with required fields
    - **Validates: Requirements 7.6**

- [x] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The project uses PHP/Laravel, so all implementations follow Laravel conventions (Eloquent models, migrations, Blade views, service injection)
- The audit log write failure is non-blocking per design error handling specification

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "2.1"] },
    { "id": 1, "tasks": ["3.1", "3.3", "4.1", "4.2"] },
    { "id": 2, "tasks": ["3.2", "3.4", "3.5", "4.3"] },
    { "id": 3, "tasks": ["3.6", "3.7", "6.1", "6.2"] },
    { "id": 4, "tasks": ["6.3", "7.1", "7.2", "7.3"] },
    { "id": 5, "tasks": ["8.1", "8.2", "8.3", "8.4"] },
    { "id": 6, "tasks": ["8.5", "10.1", "10.2"] },
    { "id": 7, "tasks": ["10.3"] }
  ]
}
```
