# Requirements Document

## Introduction

Reorganización del módulo de Informes del sistema SAPP. El objetivo es consolidar informes redundantes, unificar funcionalidades similares, mejorar informes existentes y agregar un nuevo informe de búsqueda por términos. El resultado final será un módulo de informes más limpio, con menos opciones pero más potentes y completas.

## Glossary

- **SAPP**: Sistema de Administración y Procesamiento de Peticiones (la aplicación principal)
- **Report_Module**: Módulo de informes accesible en `/reports` que muestra tarjetas con los diferentes tipos de informes disponibles
- **Cut**: Corte; agrupación de solicitudes de servicio por periodo de actividad
- **Service_Request**: Solicitud de servicio registrada en el sistema
- **Timeline**: Línea de tiempo que muestra la cronología de eventos de una solicitud
- **SLA**: Acuerdo de Nivel de Servicio (Service Level Agreement)
- **Service_Family**: Familia de servicios; agrupación lógica de servicios relacionados bajo un contrato
- **Search_Report**: Nuevo informe que permite buscar y analizar solicitudes por términos o tipos de servicio

## Requirements

### Requirement 1: Conservar informes de Cortes

**User Story:** Como usuario del sistema, quiero mantener los informes de Cortes e Informe Analítico por Corte sin cambios, para seguir accediendo al análisis por periodos de actividad.

#### Acceptance Criteria

1. THE Report_Module SHALL display the "Cortes" report card in the reports index page, providing navigation to the cut listing where the user can create, edit, view, and synchronize cuts and their associated service requests
2. WHEN a user accesses a specific cut, THE Report_Module SHALL provide export options in PDF and ZIP formats for the cut's service requests grouped by service family
3. THE Report_Module SHALL display the "Informe Analítico por Corte" report card in the reports index page, providing access to a per-cut analytical view that includes a summary section, distribution breakdowns (by status, channel, area, family, service, sub-service, and route), findings, and recommendations
4. WHEN a user accesses the Informe Analítico por Corte for a specific cut, THE Report_Module SHALL provide export options in PDF and CSV formats

### Requirement 2: Unificar informes de Línea de Tiempo

**User Story:** Como usuario del sistema, quiero un único informe de Línea de Tiempo que combine la funcionalidad de "Timeline por Ticket" y "Línea de Tiempo", para no tener dos informes redundantes que hacen lo mismo.

#### Acceptance Criteria

1. THE Report_Module SHALL display a single "Línea de Tiempo" report card that replaces both "Timeline por Ticket" and the previous "Línea de Tiempo" cards
2. WHEN a user accesses the unified Timeline report, THE Report_Module SHALL provide a search field that accepts a full or partial ticket number and returns matching service requests
3. WHEN a user accesses the unified Timeline report, THE Report_Module SHALL display a paginated list of service requests (10 items per page) filtered by date range, defaulting to the current month when no date range is specified
4. WHEN a user selects a service request from the list or searches by ticket number, THE Report_Module SHALL display the full timeline detail including chronological events, time-in-status metrics, and resolution statistics
5. WHEN a user views a timeline detail, THE Report_Module SHALL offer export options in PDF and Excel formats
6. THE Report_Module SHALL remove the separate "Timeline por Ticket" card from the reports index page
7. IF a user searches by ticket number and no matching service request is found, THEN THE Report_Module SHALL display a message indicating no results were found and suggest verifying the ticket number

### Requirement 3: Mejorar Reporte por Rango de Tiempo con soporte de Cortes

**User Story:** Como usuario del sistema, quiero que el Reporte por Rango de Tiempo permita seleccionar un corte existente como fuente de rango de fechas, para generar reportes detallados basados en los periodos definidos por los cortes.

#### Acceptance Criteria

1. THE Report_Module SHALL display the "Reporte por Rango de Tiempo" report card in the reports index
2. WHEN a user accesses the Time Range report, THE Report_Module SHALL display a list of available cuts belonging to the current workspace's active contract, ordered by start date descending, as an option to use as the date range source
3. WHEN a user selects a cut as date range source, THE Report_Module SHALL populate the start and end dates from the selected cut's start_date and end_date and disable manual editing of those date fields
4. WHEN a user selects a cut as date range source, THE Report_Module SHALL filter service requests to only those associated with the selected cut via the cut_service_request relationship
5. THE Report_Module SHALL continue to allow manual date range selection as an alternative to cut-based selection, with both modes being mutually exclusive
6. THE Report_Module SHALL generate reports in PDF, Excel, and ZIP formats regardless of whether the date range was set manually or from a cut
7. THE Report_Module SHALL allow filtering by service families in both manual and cut-based modes, applying the family filter as an additional constraint on the result set
8. IF a user selects a cut that has no associated service requests, THEN THE Report_Module SHALL display a message indicating that the selected cut contains no service requests and prevent report generation
9. IF no cuts exist for the current workspace's active contract, THEN THE Report_Module SHALL hide the cut selection option and default to manual date range input

### Requirement 4: Unificar Cumplimiento de SLA con Rendimiento de Servicios

**User Story:** Como usuario del sistema, quiero un único informe que combine el cumplimiento de SLA y el rendimiento de servicios, para tener una visión integral del desempeño de los servicios en un solo lugar.

#### Acceptance Criteria

1. THE Report_Module SHALL display a single "Servicios y SLA" report card that replaces both "Cumplimiento de SLA" and "Rendimiento de Servicios" cards
2. WHEN a user accesses the unified Services and SLA report, THE Report_Module SHALL display SLA compliance rates grouped by service and service family, where compliance is defined as the percentage of service requests resolved within their SLA deadline (not overdue)
3. WHEN a user accesses the unified Services and SLA report, THE Report_Module SHALL display performance metrics per service including total requests, average resolution time in hours, and resolved count
4. THE Report_Module SHALL allow filtering the unified report by date range (defaulting to the last 30 days), requester, and department
5. THE Report_Module SHALL provide export options in PDF and CSV formats for the unified report
6. THE Report_Module SHALL remove the separate "Cumplimiento de SLA" and "Rendimiento de Servicios" cards from the reports index page
7. IF the applied filters return no matching service requests, THEN THE Report_Module SHALL display a message indicating that no data is available for the selected criteria

### Requirement 5: Unificar Estado, Criticidad y Tendencias Mensuales

**User Story:** Como usuario del sistema, quiero un único informe que combine solicitudes por estado, niveles de criticidad y tendencias mensuales, para tener un panorama completo de la operación en un solo lugar.

#### Acceptance Criteria

1. THE Report_Module SHALL display a single "Panorama Operativo" report card that replaces "Solicitudes por Estado", "Niveles de Criticidad", and "Tendencias Mensuales" cards
2. WHEN a user accesses the unified Operational Overview report, THE Report_Module SHALL display the distribution of service requests by status showing each status name, its count, and its percentage of the total (rounded to 2 decimal places)
3. WHEN a user accesses the unified Operational Overview report, THE Report_Module SHALL display the distribution of service requests by criticality level showing each level's count and average resolution time in hours (rounded to 1 decimal place)
4. WHEN a user accesses the unified Operational Overview report, THE Report_Module SHALL display monthly trend data showing, for each month: total requests, resolved requests, completion rate (percentage of requests in status RESUELTA or CERRADA relative to total), and average resolution time in hours
5. WHEN a user accesses the unified Operational Overview report without specifying a date range, THE Report_Module SHALL default to the last 30 days for the status and criticality sections, and default to 12 months for the trends section
6. WHEN a user applies a date range filter, THE Report_Module SHALL update the status distribution and criticality distribution sections to reflect only service requests created within the selected range
7. THE Report_Module SHALL allow configuring the number of months displayed in the trends section with options of 3, 6, 12, or 24 months, defaulting to 12 months
8. THE Report_Module SHALL provide export options in PDF and CSV formats for the unified report
9. IF no service requests exist for the selected date range or month configuration, THEN THE Report_Module SHALL display a message indicating that no data is available for the selected period
10. THE Report_Module SHALL remove the separate "Solicitudes por Estado", "Niveles de Criticidad", and "Tendencias Mensuales" cards from the reports index page

### Requirement 6: Nuevo informe de búsqueda por términos

**User Story:** Como usuario del sistema, quiero un informe que me permita buscar y analizar solicitudes por uno o varios términos de búsqueda o por tipos de servicio, para encontrar patrones y obtener información cruzada de las solicitudes.

#### Acceptance Criteria

1. THE Report_Module SHALL display a "Búsqueda y Análisis" report card in the reports index page
2. WHEN a user accesses the Search and Analysis report, THE Report_Module SHALL provide a text input field that accepts one or multiple search terms separated by commas, up to a maximum of 10 terms with each term having a maximum length of 100 characters
3. WHEN a user accesses the Search and Analysis report, THE Report_Module SHALL provide filter options to select one or multiple service types (service families, services, or sub-services)
4. WHEN a user submits a search query, THE Report_Module SHALL perform case-insensitive partial matching across service request titles, descriptions, resolution notes, and requester name, email, and department
5. WHEN a user submits a search with multiple terms, THE Report_Module SHALL return results that match any of the provided terms (OR logic)
6. WHEN search results are returned, THE Report_Module SHALL display a summary with total matches, distribution by status, distribution by service family, and distribution by criticality level
7. WHEN search results are returned, THE Report_Module SHALL display a paginated list of matching service requests showing ticket number, title, status, service, creation date, and resolution date, ordered by creation date descending, with a maximum of 50 results per page
8. WHEN a user provides both search terms and service type filters, THE Report_Module SHALL return only results that match at least one search term AND belong to at least one of the selected service types
9. THE Report_Module SHALL provide export options in PDF and CSV formats for search results
10. IF no results match the search criteria, THEN THE Report_Module SHALL display a message indicating zero matches were found and listing the search terms and filters that were applied
11. IF a user submits the search form without entering any search terms and without selecting any service type filter, THEN THE Report_Module SHALL display a validation message requesting at least one search term or one service type filter

### Requirement 7: Actualizar la página índice de informes

**User Story:** Como usuario del sistema, quiero que la página principal de informes refleje la nueva estructura consolidada, para navegar fácilmente entre los informes disponibles.

#### Acceptance Criteria

1. THE Report_Module SHALL display exactly 7 report cards in the reports index page, in the following order: Cortes, Informe Analítico por Corte, Línea de Tiempo, Reporte por Rango de Tiempo, Servicios y SLA, Panorama Operativo, and Búsqueda y Análisis
2. THE Report_Module SHALL remove the following cards from the reports index page: "Estadísticas Rápidas", "Timeline por Ticket", "Cumplimiento de SLA", "Solicitudes por Estado", "Niveles de Criticidad", "Rendimiento de Servicios", and "Tendencias Mensuales"
3. THE Report_Module SHALL display the report cards in a grid layout of 1 column on viewports below 640px, 2 columns on viewports from 640px to 1023px, and 3 columns on viewports of 1024px and above
4. THE Report_Module SHALL display each report card with a left border of 4px width using a distinct color per card so that no two cards share the same border color
5. WHEN a user clicks on a report card, THE Report_Module SHALL navigate to the corresponding report page for that card
