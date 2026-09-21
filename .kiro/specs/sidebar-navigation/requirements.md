# Requirements Document

## Introduction

Migración de la navegación principal de SAPP desde una barra superior horizontal (top navbar) hacia un menú lateral izquierdo colapsable (sidebar), inspirado en el patrón de "rail de íconos" de editores modernos: navegación arriba, elementos de contexto (entidad/workspace, alertas, usuario) agrupados al pie del propio sidebar, y capacidad de colapsar a solo íconos para recuperar ancho de pantalla en trabajo denso.

El objetivo es mejorar la ergonomía de una app de operación diaria (gestión de portales web) donde el técnico pasa horas navegando entre solicitudes, tareas y reportes. El cambio NO debe perder ninguna funcionalidad actual del navbar (dropdowns, selector de entidad, campana de alertas con polling, menú de usuario, estados activos, menú móvil).

## Glossary

- **SAPP**: Sistema de Administración y Procesamiento de Peticiones (la aplicación principal)
- **Top_Navbar**: La barra de navegación horizontal superior actual, definida en `resources/views/layouts/app.blade.php` dentro de `<nav id="mainNavigation">`
- **Sidebar**: El nuevo menú lateral izquierdo que reemplaza al Top_Navbar como navegación principal
- **Rail_Mode**: Estado colapsado del Sidebar donde solo se muestran íconos (angosto), sin etiquetas de texto
- **Expanded_Mode**: Estado expandido del Sidebar donde se muestran íconos + etiquetas de texto
- **Nav_Section**: Cada una de las agrupaciones de navegación actuales (Solicitudes, Gestión, Reportes, Configuración), hoy definidas en el array `$navSections`
- **Context_Footer**: Zona inferior del Sidebar que agrupa el selector de entidad/contrato (workspace), la campana de alertas y el menú de usuario
- **Workspace_Selector**: El selector de entidad/contrato implementado como dropdown (feature previa), que permite cambiar de entidad sin perder la página actual
- **Alert_Bell**: La campana de alertas operativas con badge y dropdown de alertas recientes (polling periódico)
- **Mobile_Drawer**: Comportamiento del Sidebar en pantallas pequeñas, donde queda oculto y se despliega superpuesto mediante un botón hamburguesa
- **Active_State**: El resaltado visual del ítem de navegación correspondiente a la ruta actual, resuelto hoy con `request()->routeIs(...)`

## Requirements

### Requirement 1: Sidebar como navegación principal

**User Story:** Como usuario del sistema, quiero un menú lateral izquierdo en lugar de la barra superior, para tener la navegación siempre visible en vertical y ganar espacio horizontal en las pantallas de trabajo.

#### Acceptance Criteria

1. THE Sidebar SHALL replace the Top_Navbar as the primary navigation on all authenticated pages that extend the main layout.
2. THE Sidebar SHALL render every existing Nav_Section (Solicitudes, Gestión, Reportes, Configuración) with the same items, routes and icons currently defined in `$navSections`.
3. THE Sidebar SHALL preserve the Active_State highlighting for the section and item matching the current route, using the same route-matching logic as today.
4. THE Sidebar SHALL place the SAPP logo at the top, linking to `my-space.index` (the current behavior of the logo).
5. WHEN a Nav_Section contains sub-items, THE Sidebar SHALL make them reachable (expandable group in Expanded_Mode, or flyout on hover/click in Rail_Mode) without losing any item currently accessible from the Top_Navbar dropdowns.

### Requirement 2: Colapso a rail de íconos

**User Story:** Como técnico que trabaja solicitudes durante horas, quiero poder colapsar el menú a solo íconos, para recuperar ancho de pantalla cuando lo necesito.

#### Acceptance Criteria

1. THE Sidebar SHALL support two states: Rail_Mode (icons only, narrow) and Expanded_Mode (icons + labels).
2. THE Sidebar SHALL provide an explicit control (toggle button) to switch between Rail_Mode and Expanded_Mode.
3. WHEN the user switches mode, THE Sidebar SHALL persist the preference so it is restored on the next page load and across sessions on the same browser.
4. WHILE in Rail_Mode, THE Sidebar SHALL show a tooltip or label on hover for each icon so the user can identify items without expanding.
5. THE main content area SHALL adjust its available width to the current Sidebar state without horizontal overflow or layout breakage.

### Requirement 3: Contexto agrupado al pie del sidebar

**User Story:** Como usuario, quiero que el selector de entidad, las alertas y mi usuario estén agrupados al pie del menú lateral, para tener el contexto y las acciones de cuenta en un lugar consistente y separado de la navegación.

#### Acceptance Criteria

1. THE Context_Footer SHALL be located at the bottom of the Sidebar, visually separated from the navigation items.
2. THE Context_Footer SHALL contain the Workspace_Selector, the Alert_Bell, and the user menu (with logout).
3. THE Workspace_Selector in the Context_Footer SHALL preserve its current behavior: list the user's contracts/entities and switch without leaving the current page (redirect_to = current URL).
4. THE Alert_Bell in the Context_Footer SHALL preserve its current behavior: unread badge, recent-alerts dropdown, and periodic polling.
5. WHILE in Rail_Mode, THE Context_Footer SHALL still expose the Workspace_Selector, Alert_Bell and user menu as icons with flyout/dropdown, without losing any action available today.

### Requirement 4: Comportamiento responsive (hamburguesa)

**User Story:** Como usuario en móvil o tablet, quiero que el menú lateral no ocupe la pantalla permanentemente, para poder usar la app cómodamente en espacios reducidos.

#### Acceptance Criteria

1. WHILE on small screens, THE Sidebar SHALL be hidden by default and toggled as a Mobile_Drawer via a hamburger button.
2. WHEN the Mobile_Drawer is open, THE Sidebar SHALL overlay the content with a dismissible backdrop.
3. WHEN the user selects a navigation item in the Mobile_Drawer, THE Sidebar SHALL navigate and close the drawer.
4. THE Mobile_Drawer SHALL expose all Nav_Sections and the Context_Footer (workspace, alerts, user) without losing any item available on desktop.

### Requirement 5: Sin pérdida de funcionalidad ni regresiones

**User Story:** Como responsable del sistema, quiero que el cambio de navegación no rompa ninguna pantalla existente ni pierda funciones, para adoptar el sidebar con confianza.

#### Acceptance Criteria

1. THE main layout SHALL continue to expose the same content slots (`@yield('content')`, `@yield('title')`, `@yield('breadcrumb')`, flash messages) so that no existing view requires changes to render.
2. THE application SHALL keep all current routes, controllers and JavaScript behaviors (dropdown logic, alert polling, workspace switch) working after the migration.
3. WHEN the full test suite runs after the migration, THE application SHALL not introduce new test failures beyond the pre-existing baseline.
4. THE Blade views SHALL compile without errors (`view:cache`) after the migration.
5. WHERE the previous Top_Navbar markup is removed, THE application SHALL not leave dead references (unused ids, orphan JS handlers) that produce console errors.

### Requirement 6: Identidad visual y accesibilidad

**User Story:** Como usuario, quiero que el sidebar sea legible durante uso prolongado y conserve la identidad de la app, para trabajar sin fatiga visual.

#### Acceptance Criteria

1. THE Sidebar SHALL preserve the SAPP brand accent (current red identity and per-entity accent color) in a way legible for prolonged use.
2. THE Sidebar SHALL provide accessible navigation: keyboard focus order, `aria-current` on the active item, `aria-expanded` on expandable groups, and accessible labels for icon-only controls in Rail_Mode.
3. THE Active_State SHALL be distinguishable by more than color alone (e.g., background + indicator) to satisfy contrast and non-color-only cues.
