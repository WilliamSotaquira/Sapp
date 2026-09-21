# Design Document

## Overview

Se reemplaza la barra superior (`<nav id="mainNavigation">` en `resources/views/layouts/app.blade.php`) por un sidebar izquierdo colapsable. El diseño reutiliza al máximo las piezas ya existentes (el array `$navSections`, el `$isSectionActive`, el Workspace_Selector, la campana de alertas con su polling) para minimizar riesgo, y cambia principalmente la **estructura del layout** y el **contenedor de navegación**, no la lógica de datos.

Patrón objetivo (estilo "rail" tipo editor moderno):
- Franja vertical izquierda, fija en desktop.
- Arriba: logo. Centro: navegación (Nav_Sections). Pie: Context_Footer (workspace, alertas, usuario).
- Dos estados: Rail_Mode (solo íconos, ~64px) y Expanded_Mode (íconos + etiqueta, ~240px).
- En móvil: oculto, se abre como Mobile_Drawer con hamburguesa + backdrop.

## Architecture

### Estructura de layout actual (a modificar)

Hoy `app.blade.php` tiene: `<nav>` superior full-width, y debajo `<div class="max-w-7xl mx-auto ...">` con el contenido. El sidebar cambia esto a un layout de dos columnas:

```
<body>
  <div class="app-shell">              {{-- flex row --}}
    <aside id="appSidebar" ...>        {{-- sidebar (rail/expanded) --}}
       <div>logo</div>
       <nav>nav-sections</nav>
       <div>context-footer</div>
    </aside>
    <div class="app-main">             {{-- columna de contenido --}}
       <header class="app-topbar">      {{-- barra superior mínima: hamburguesa + título/breadcrumb --}}
       <main>@yield('content')</main>
    </div>
  </div>
</body>
```

Notas:
- El contenido conserva `@yield('content')`, `@yield('title')`, `@yield('breadcrumb')` y los flash messages, para no tocar ninguna vista (Requirement 5.1).
- El ancho del contenido deja de ser `max-w-7xl mx-auto` fijo y pasa a ocupar el espacio restante junto al sidebar (con un padding interno). Se conserva un `max-w` interno opcional para lectura cómoda.

### Componentización

Para mantener `app.blade.php` legible y aislar el cambio, se extrae el sidebar a un partial:
- `resources/views/layouts/partials/sidebar.blade.php` — el sidebar completo (logo, navegación, context-footer).
- `resources/views/layouts/partials/topbar.blade.php` — la barra superior mínima (hamburguesa en móvil, título de página / breadcrumb).

El array `$navSections` y el closure `$isSectionActive` se mueven a un **View Composer** o a un `@php` compartido incluido antes de los partials, de modo que ambos (sidebar desktop y drawer móvil) consuman la misma fuente de verdad. Recomendado: View Composer sobre el partial del sidebar para no repetir el array.

### Estado colapsado/expandido (Rail vs Expanded)

- Estado controlado con Alpine (`x-data`) a nivel del `app-shell`, con una propiedad `collapsed` inicializada desde `localStorage` (clave `sapp_sidebar_collapsed`).
- El toggle escribe en `localStorage` y alterna clases. Persistencia solo en navegador (Requirement 2.3) — no requiere backend.
- Rail_Mode: `aside` con ancho ~64px, etiquetas ocultas, tooltips al hover.
- Expanded_Mode: ~240px, etiquetas visibles.
- La columna de contenido usa `margin-left`/`padding-left` reactivo al estado (transición suave).

### Navegación con sub-secciones

Las Nav_Sections hoy son dropdowns. En el sidebar:
- **Expanded_Mode**: grupo expandible/acordeón (click abre/cierra los sub-ítems inline). `aria-expanded` en el disparador.
- **Rail_Mode**: al hover/click sobre el ícono, un flyout lateral muestra los sub-ítems (posición absoluta a la derecha del rail).
- Reutiliza `$isSectionActive` para marcar activo el grupo y el sub-ítem.

### Context_Footer (workspace, alertas, usuario)

Se reubican al pie del `aside`, separados por un divisor:
- **Workspace_Selector**: se mueve el markup del dropdown actual (forms POST a `workspaces.switch` con `redirect_to = request()->fullUrl()`). En Rail_Mode se muestra como ícono/logo de la entidad con flyout hacia arriba.
- **Alert_Bell**: se mueve el botón + dropdown + el script de polling tal cual. El polling (`operational-alerts.api.unread-count` / `.recent`) no cambia; solo cambia la posición del contenedor. Se conservan los ids (`navAlertBell`, `navAlertBadge`, `alertDropdown`, `alertDropdownList`, `alertBellWrapper`) para no romper el JS existente (Requirement 5.2, 5.5).
- **Menú de usuario**: nombre + logout (form POST), como hoy.

### Responsive / Mobile_Drawer

- Breakpoint: `lg`. En `< lg`, el `aside` se posiciona `fixed` fuera de pantalla (`-translate-x-full`) y se muestra al abrir el drawer.
- Botón hamburguesa en la `app-topbar` (visible solo en `< lg`).
- Backdrop semitransparente que cierra el drawer al hacer click.
- Al navegar a un ítem, el drawer se cierra.
- Se reutiliza (o se adapta) el manejo de estado móvil actual; el nuevo estado Alpine del shell centraliza `mobileOpen`.

## Components and Interfaces

### Archivos afectados

| Archivo | Cambio |
|---|---|
| `resources/views/layouts/app.blade.php` | Reestructura a app-shell (sidebar + main). Elimina `<nav id="mainNavigation">` y su menú móvil actual. Incluye los partials. |
| `resources/views/layouts/partials/sidebar.blade.php` | NUEVO. Sidebar completo (logo, nav, context-footer), con Rail/Expanded y flyouts. |
| `resources/views/layouts/partials/topbar.blade.php` | NUEVO. Barra superior mínima (hamburguesa, título/breadcrumb, contenedor de flash si aplica). |
| `app/Providers/AppServiceProvider.php` o un `ViewServiceProvider` | Registrar View Composer que comparte `$navSections` e `$isSectionActive` con los partials del sidebar. (Alternativa: `@php` en un partial `_nav-data.blade.php` incluido.) |
| CSS dentro de `app.blade.php` (`@push('styles')` o bloque `<style>`) | Estilos del sidebar: rail/expanded, transiciones, flyouts, tooltips, estados activos. Reusar variables de acento por entidad ya calculadas (`$workspaceAccent`). |

### Contrato de layout (para no tocar vistas)

Se mantienen intactos: `@yield('content')`, `@yield('title')`, `@yield('breadcrumb')`, `@section('hidePageHeader')`, `@section('disableGlobalFlash')`, y los bloques de flash messages. Cualquier vista que hoy extiende `layouts.app` seguirá funcionando sin cambios (Requirement 5.1).

### Datos de navegación (fuente única)

`$navSections` conserva su forma actual (key, label, icon, type, match, links[]). No se modifica su contenido; solo se consume desde el partial del sidebar en vez de inline en el navbar. `$isSectionActive($patterns)` se conserva idéntico.

## Data Models

No hay cambios de modelos ni migraciones. La única persistencia nueva es la preferencia de colapso en `localStorage` (cliente), sin tabla ni columna.

## Error Handling

- Si `$currentWorkspace`/`$userContracts` no están disponibles (sesión sin entidad), el Context_Footer degrada igual que hoy: el Workspace_Selector muestra el acceso a `workspaces.select` y la navegación sigue operativa.
- Si el polling de alertas falla, la campana conserva su manejo actual (no bloquea la navegación).
- El estado de `localStorage` se lee con guarda try/catch; si falla, se asume Expanded_Mode por defecto.

## Testing Strategy

Este cambio es principalmente de layout/frontend (Blade + Alpine + CSS), sin lógica de negocio nueva. La estrategia:

1. **Compilación de vistas**: `php artisan view:cache` debe pasar sin errores tras cada fase (Requirement 5.4).
2. **Suite existente**: `php artisan test` debe mantener el baseline (1197 passed / 53 failed preexistentes) sin nuevas regresiones (Requirement 5.3). Los tests de Feature que renderizan páginas autenticadas (p. ej. dashboard, listados) ejercitan el layout; si alguno assertsea markup del navbar viejo, se ajusta.
3. **Verificación manual guiada** (checklist): navegación a cada sección, estados activos, colapso/expansión con persistencia, flyouts en rail, cambio de entidad desde el footer preservando la página, campana de alertas con polling, y comportamiento del drawer en móvil.
4. **Búsqueda de referencias muertas**: grep por ids del navbar viejo (`mainNavigation`, `mobileMenuPanel`, `data-mobile-menu-toggle`, etc.) para eliminar handlers huérfanos (Requirement 5.5).

## Rollout / Riesgo

- Blast radius alto: todas las vistas extienden `layouts.app`. Por eso se ejecuta por fases (ver tasks.md) con verificación de compilación y suite tras cada fase.
- Estrategia de reversibilidad: el trabajo va en su propia rama/commits por fase, de modo que cualquier fase se pueda revertir aislada.
