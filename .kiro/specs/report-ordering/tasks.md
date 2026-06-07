# Implementation Plan: Report Ordering

## Overview

Reorganización del módulo de Informes de SAPP: consolidar 10+ tarjetas en 7, unificando informes redundantes, mejorando el Reporte por Rango de Tiempo con soporte de cortes, y agregando un nuevo informe de Búsqueda y Análisis. Se reutiliza la lógica de consulta existente refactorizándola en nuevos controladores unificados.

## Tasks

- [x] 1. Create unified controllers and route structure
  - [x] 1.1 Create UnifiedTimelineController with index, show, searchByTicket, and export methods
    - Create `app/Http/Controllers/Reports/UnifiedTimelineController.php`
    - Implement `index()`: paginated list (10/page) with date range filter (default: current month) and ticket search field
    - Implement `show(int $id)`: display full timeline detail for a service request
    - Implement `searchByTicket(Request $request)`: partial/full ticket match, redirect to detail if single result, otherwise show filtered list
    - Implement `export(int $id, string $format)`: export timeline in PDF and Excel formats
    - Reuse query logic from existing `TimelineReportController`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.7_

  - [x] 1.2 Create ServicesSlaController with index and export methods
    - Create `app/Http/Controllers/Reports/ServicesSlaController.php`
    - Implement `index(Request $request)`: combine SLA compliance rates (grouped by service/family) and performance metrics (total requests, avg resolution hours, resolved count)
    - Support filters: date_from, date_to (default last 30 days), requester_id, department
    - Implement `export(Request $request, string $format)`: PDF and CSV export
    - Reuse query logic from existing `ReportController::slaCompliance()` and `ReportController::servicePerformance()`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.7_

  - [x] 1.3 Create OperationalOverviewController with index and export methods
    - Create `app/Http/Controllers/Reports/OperationalOverviewController.php`
    - Implement `index(Request $request)`: combine status distribution (name, count, percentage 2dp), criticality distribution (level, count, avg resolution hours 1dp), and monthly trends (total, resolved, completion rate, avg resolution hours)
    - Support filters: date_from, date_to (default 30 days for status/criticality), months (3/6/12/24, default 12 for trends)
    - Implement `export(Request $request, string $format)`: PDF and CSV export
    - Reuse query logic from existing `ReportController::requestsByStatus()`, `criticalityLevels()`, and `monthlyTrends()`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_

  - [x] 1.4 Create SearchAnalysisController with index, search, and export methods
    - Create `app/Http/Controllers/Reports/SearchAnalysisController.php`
    - Implement `index()`: display search form with text input (comma-separated terms, max 10, max 100 chars each) and multi-select for families/services/sub-services
    - Implement `search(Request $request)`: case-insensitive partial match across title, description, resolution_notes, requester.name, requester.email, requester.department; OR logic between terms; AND with service type filters; paginated 50/page, ordered by created_at desc; include summary (total_matches, by_status, by_family, by_criticality)
    - Implement `export(Request $request, string $format)`: PDF and CSV export
    - Validate: at least one term OR one service type filter; max 10 terms; max 100 chars per term
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.10, 6.11_

  - [x] 1.5 Register new routes in `routes/features/reporting/web.php`
    - Add route group for unified timeline: `timeline/` with index, show, search, export
    - Add route group for services-sla: `services-sla/` with index, export
    - Add route group for operational-overview: `operational-overview/` with index, export
    - Add route group for search-analysis: `search-analysis/` with index, search, export
    - Keep existing cut routes and cut analytics routes unchanged
    - Deprecate old routes (sla-compliance, requests-by-status, criticality-levels, service-performance, monthly-trends) by adding redirect responses
    - _Requirements: 2.1, 4.1, 5.1, 6.1, 7.5_

- [x] 2. Enhance TimeRangeReportController with cut support
  - [x] 2.1 Add cut selection logic to TimeRangeReportController
    - Modify `index()` to load available cuts from the current workspace's active contract, ordered by start_date desc
    - Modify `generate()` to accept `cut_id` as alternative to manual date range (mutually exclusive)
    - When `cut_id` is present: use cut's start_date/end_date and filter via cut_service_request relationship
    - Validate: cut with no associated requests shows error and prevents generation
    - Hide cut selector in view when no cuts exist for the active contract
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

  - [x] 2.2 Write property test for cut-based filtering (Property 3)
    - **Property 3: Cut-based and family filtering constrains results**
    - **Validates: Requirements 3.4, 3.7**

- [ ] 3. Checkpoint - Ensure controllers and routes work
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Create Blade views for unified reports
  - [x] 4.1 Create unified timeline views
    - Create `resources/views/reports/timeline/index.blade.php`: paginated list with search field, date range filter, default current month
    - Create `resources/views/reports/timeline/show.blade.php`: full timeline detail with chronological events, time-in-status metrics, resolution statistics, and export buttons (PDF/Excel)
    - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.7_

  - [x] 4.2 Create time-range report view with cut selector
    - Modify `resources/views/reports/time-range/index.blade.php`: add cut selector dropdown (hidden when no cuts), disable manual date fields when cut selected, keep family filter available in both modes
    - Add JavaScript to toggle between manual and cut-based date selection (mutually exclusive)
    - _Requirements: 3.2, 3.3, 3.5, 3.7, 3.8, 3.9_

  - [x] 4.3 Create Services and SLA view
    - Create `resources/views/reports/services-sla/index.blade.php`: display SLA compliance table (service, family, total, compliant, overdue, rate) and performance table (service, family, total, avg hours, resolved); include date range, requester, and department filters; export buttons (PDF/CSV); empty state message
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.7_

  - [x] 4.4 Create Operational Overview view
    - Create `resources/views/reports/operational-overview/index.blade.php`: three sections - status distribution (table with name, count, percentage), criticality distribution (table with level, count, avg hours), monthly trends (table with month, total, resolved, completion rate, avg hours); date range filter, months selector (3/6/12/24); export buttons (PDF/CSV); empty state message
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_

  - [x] 4.5 Create Search and Analysis views
    - Create `resources/views/reports/search-analysis/index.blade.php`: search form with text input for terms (comma-separated), multi-select for families/services/sub-services
    - Create `resources/views/reports/search-analysis/results.blade.php`: summary panel (total matches, by status, by family, by criticality), paginated results table (ticket, title, status, service, created_at, resolved_at), export buttons (PDF/CSV), no-results message with applied terms/filters
    - _Requirements: 6.2, 6.3, 6.6, 6.7, 6.9, 6.10, 6.11_

- [x] 5. Update reports index page
  - [x] 5.1 Redesign reports index view with exactly 7 cards
    - Modify `resources/views/reports/index.blade.php`: display exactly 7 cards in order: Cortes, Informe Analítico por Corte, Línea de Tiempo, Reporte por Rango de Tiempo, Servicios y SLA, Panorama Operativo, Búsqueda y Análisis
    - Remove cards: Estadísticas Rápidas, Timeline por Ticket, Cumplimiento de SLA, Solicitudes por Estado, Niveles de Criticidad, Rendimiento de Servicios, Tendencias Mensuales
    - Apply responsive grid: 1 column below 640px, 2 columns 640-1023px, 3 columns 1024px+
    - Each card with 4px left border in a distinct color (no two cards share the same color)
    - Remove stats calculation from `ReportController::index()` (return view without data)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [x] 5.2 Write unit tests for index page structure
    - Verify exactly 7 cards rendered, correct order, correct routes
    - Verify responsive grid classes (sm:grid-cols-2, lg:grid-cols-3)
    - Verify each card has unique border color
    - _Requirements: 7.1, 7.3, 7.4_

- [ ] 6. Checkpoint - Ensure views render correctly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Implement export functionality for new controllers
  - [x] 7.1 Create PDF export views for unified reports
    - Create `resources/views/reports/exports/services-sla-pdf.blade.php`
    - Create `resources/views/reports/exports/operational-overview-pdf.blade.php`
    - Create `resources/views/reports/exports/search-analysis-pdf.blade.php`
    - Reuse existing PDF layout patterns from `reports/exports/` directory
    - _Requirements: 4.5, 5.8, 6.9_

  - [x] 7.2 Implement CSV export logic in new controllers
    - Add CSV formatting methods to ServicesSlaController (SLA + performance data)
    - Add CSV formatting methods to OperationalOverviewController (status + criticality + trends data)
    - Add CSV formatting methods to SearchAnalysisController (search results with summary)
    - Handle empty data exports (generate file with headers only)
    - _Requirements: 4.5, 5.8, 6.9_

- [ ] 8. Implement property-based tests
  - [ ] 8.1 Write property test for ticket search partial match (Property 1)
    - **Property 1: Ticket search partial match**
    - **Validates: Requirements 2.2**

  - [ ] 8.2 Write property test for timeline pagination (Property 2)
    - **Property 2: Timeline pagination respects page size and date range**
    - **Validates: Requirements 2.3**

  - [ ] 8.3 Write property test for SLA compliance metrics (Property 4)
    - **Property 4: SLA compliance and performance metrics correctness**
    - **Validates: Requirements 4.2, 4.3**

  - [ ] 8.4 Write property test for Services and SLA filter application (Property 5)
    - **Property 5: Filter application constrains results in Services and SLA report**
    - **Validates: Requirements 4.4**

  - [ ] 8.5 Write property test for status and criticality percentages (Property 6)
    - **Property 6: Status and criticality percentage calculations**
    - **Validates: Requirements 5.2, 5.3, 5.4**

  - [ ] 8.6 Write property test for search input validation (Property 7)
    - **Property 7: Search input validation**
    - **Validates: Requirements 6.2**

  - [ ] 8.7 Write property test for search results matching (Property 8)
    - **Property 8: Search results contain matching terms**
    - **Validates: Requirements 6.4, 6.6**

  - [ ] 8.8 Write property test for multi-term OR logic (Property 9)
    - **Property 9: Multi-term OR logic produces union**
    - **Validates: Requirements 6.5**

  - [ ] 8.9 Write property test for combined search filters (Property 10)
    - **Property 10: Combined search filters produce intersection**
    - **Validates: Requirements 6.8**

  - [ ] 8.10 Write property test for search pagination and ordering (Property 11)
    - **Property 11: Search pagination and ordering**
    - **Validates: Requirements 6.7**

- [x] 9. Wire everything together and deprecate old routes
  - [x] 9.1 Update navigation and remove deprecated report methods
    - Remove `slaCompliance()`, `requestsByStatus()`, `criticalityLevels()`, `servicePerformance()`, `monthlyTrends()` from `ReportController` (keep private helper methods used by exports)
    - Simplify `ReportController::index()` to return view without stats data
    - Add redirect routes for old URLs to new unified report URLs (prevent 404 on bookmarks)
    - Update any sidebar/navigation links that reference old report routes
    - _Requirements: 2.6, 4.6, 5.10, 7.2_

  - [x] 9.2 Write integration tests for route accessibility and redirects
    - Test all new routes respond with 200 for authenticated users
    - Test old deprecated routes redirect to new URLs
    - Test export endpoints generate files without errors
    - Test workspace scoping via company_id in all queries
    - _Requirements: 2.1, 4.1, 5.1, 6.1, 7.5_

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Existing controllers (CutController, CutAnalyticsReportController) remain unchanged
- Old routes are deprecated with redirects rather than deleted to avoid breaking bookmarks
- The project uses Laravel with Blade views, DomPDF for PDF generation, and Maatwebsite Excel for spreadsheet exports

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["1.5", "2.1"] },
    { "id": 2, "tasks": ["2.2", "4.1", "4.2", "4.3", "4.4", "4.5"] },
    { "id": 3, "tasks": ["5.1"] },
    { "id": 4, "tasks": ["5.2", "7.1", "7.2"] },
    { "id": 5, "tasks": ["8.1", "8.2", "8.3", "8.4", "8.5", "8.6", "8.7", "8.8", "8.9", "8.10"] },
    { "id": 6, "tasks": ["9.1"] },
    { "id": 7, "tasks": ["9.2"] }
  ]
}
```
