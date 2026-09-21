# Implementation Plan

Migración del Top_Navbar a Sidebar izquierdo colapsable, por fases con verificación tras cada una. El orden minimiza el blast radius: primero se prepara la fuente de datos y el esqueleto sin romper lo actual, luego se migra pieza por pieza, y al final se retira el navbar viejo.

- [ ] 1. Preparar fuente única de datos de navegación
  - Extraer `$navSections` y `$isSectionActive` a un View Composer (o partial `_nav-data.blade.php`) para que sidebar y drawer los compartan sin duplicar.
  - No cambiar aún el layout; solo dejar los datos disponibles.
  - Verificar: `view:cache` compila; la app sigue mostrando el navbar actual intacto.
  - _Requirements: 1.2, 1.3, 5.1, 5.2_

- [ ] 2. Crear el partial del sidebar (desktop, Expanded_Mode)
  - Nuevo `layouts/partials/sidebar.blade.php`: logo (→ `my-space.index`), navegación con Nav_Sections como grupos expandibles, estados activos con `$isSectionActive` (`aria-current`, `aria-expanded`).
  - Aún sin colapso ni context-footer; solo la navegación expandida.
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 6.2, 6.3_

- [ ] 3. Reestructurar el layout a app-shell (sidebar + main)
  - En `app.blade.php`: envolver en `app-shell` (flex), incluir el sidebar y mover el contenido a la columna `app-main`.
  - Crear `layouts/partials/topbar.blade.php` con la barra superior mínima (contenedor de hamburguesa + título/breadcrumb).
  - Conservar `@yield('content')`, `@yield('title')`, `@yield('breadcrumb')`, flash messages y secciones (`hidePageHeader`, `disableGlobalFlash`).
  - Mantener temporalmente el navbar viejo desactivado por un flag para poder comparar, o retirarlo en la fase 8.
  - Verificar: varias vistas (dashboard, listado de solicitudes, show) renderizan sin romper layout.
  - _Requirements: 1.1, 5.1, 5.4_

- [ ] 4. Implementar Rail_Mode / Expanded_Mode con persistencia
  - Estado Alpine `collapsed` en el app-shell, inicializado desde `localStorage` (`sapp_sidebar_collapsed`), con toggle explícito.
  - Rail: solo íconos (~64px) + tooltips en hover; Expanded: íconos + etiquetas (~240px).
  - La columna de contenido se ajusta al ancho del sidebar sin overflow.
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 6.3_

- [ ] 5. Flyouts de sub-secciones en Rail_Mode
  - En rail, al hover/click sobre un ícono con sub-ítems, mostrar flyout lateral con los links; estados activos correctos.
  - _Requirements: 1.5, 6.2_

- [ ] 6. Context_Footer: mover workspace, alertas y usuario al pie del sidebar
  - Reubicar el Workspace_Selector (dropdown con forms POST a `workspaces.switch`, `redirect_to` = URL actual) al pie, con flyout hacia arriba en Rail_Mode.
  - Reubicar la campana de alertas conservando ids (`navAlertBell`, `navAlertBadge`, `alertDropdown`, `alertDropdownList`, `alertBellWrapper`) y su script de polling sin cambios.
  - Reubicar el menú de usuario (nombre + logout POST).
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 5.2_

- [ ] 7. Responsive: Mobile_Drawer con hamburguesa
  - En `< lg`, el sidebar se oculta (`fixed`, `-translate-x-full`) y se abre como drawer con backdrop desde el botón hamburguesa de la topbar.
  - Cerrar al navegar y al hacer click en el backdrop. Exponer Nav_Sections + Context_Footer completos.
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 8. Retirar el Top_Navbar viejo y limpiar
  - Eliminar `<nav id="mainNavigation">`, el menú móvil antiguo (`mobileMenuPanel`, `data-mobile-menu-toggle`) y el JS ya no usado (`setupNavigationMenus` si queda huérfano).
  - Grep por ids/handlers viejos para no dejar referencias muertas ni errores de consola.
  - _Requirements: 5.5_

- [ ] 9. Identidad visual y acento por entidad
  - Aplicar la identidad de marca (rojo SAPP) y el acento por entidad (`$workspaceAccent`) al sidebar y al estado activo, legible para uso prolongado; estado activo distinguible por más que color.
  - _Requirements: 6.1, 6.3_

- [ ] 10. Verificación final
  - `php artisan view:cache` sin errores.
  - `php artisan test`: mantener baseline (sin nuevas regresiones); ajustar cualquier test que asserte markup del navbar viejo.
  - Checklist manual: navegación por sección, estados activos, colapso persistente, flyouts en rail, cambio de entidad preservando página, campana con polling, drawer móvil.
  - _Requirements: 5.3, 5.4, 5.5_
