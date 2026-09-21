<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Fuente única de la navegación principal del layout.
 *
 * Extrae el array de secciones de navegación (antes definido inline en
 * layouts/app.blade.php) para que tanto el navbar/sidebar de escritorio como
 * el menú móvil (y el futuro sidebar) consuman exactamente los mismos datos,
 * sin duplicar el arreglo en varias vistas.
 */
class NavigationComposer
{
    public function compose(View $view): void
    {
        $view->with('navSections', $this->sections());
        $view->with('isSectionActive', $this->activeResolver());
    }

    /**
     * Secciones de navegación. Cada sección: key, label, icon, type, match
     * (patrones de ruta para marcar activo) y links[].
     *
     * @return array<int, array<string, mixed>>
     */
    private function sections(): array
    {
        return [
            [
                'key' => 'requests',
                'label' => 'Solicitudes',
                'icon' => 'fas fa-tasks',
                'type' => 'dropdown',
                'match' => ['service-requests.*'],
                'links' => [
                    [
                        'route' => 'service-requests.create',
                        'label' => 'Crear Solicitud',
                        'icon' => 'fas fa-plus-circle',
                        'match' => ['service-requests.create'],
                    ],
                    [
                        'route' => 'service-requests.index',
                        'label' => 'Ver Solicitudes',
                        'icon' => 'fas fa-list',
                        'match' => ['service-requests.index'],
                    ],
                    [
                        'route' => 'technician-schedule.my-agenda',
                        'label' => 'Mi Agenda',
                        'icon' => 'fas fa-clipboard-list',
                        'match' => ['technician-schedule.my-agenda'],
                    ],
                    [
                        'route' => 'my-space.meetings',
                        'label' => 'Reuniones',
                        'icon' => 'fas fa-users',
                        'match' => ['my-space.meetings'],
                    ],
                ],
            ],
            [
                'key' => 'management',
                'label' => 'Gestión',
                'icon' => 'fas fa-th-large',
                'type' => 'dropdown',
                'match' => ['projects.*', 'operational-alerts.*', 'performance-metrics.*', 'tasks.*', 'standard-tasks.*', 'technician-schedule.*', 'technicians.*'],
                'links' => [
                    [
                        'route' => 'projects.index',
                        'label' => 'Proyectos',
                        'icon' => 'fas fa-project-diagram',
                        'match' => ['projects.*'],
                    ],
                    [
                        'route' => 'operational-alerts.index',
                        'label' => 'Alertas Operativas',
                        'icon' => 'fas fa-bell',
                        'match' => ['operational-alerts.index'],
                    ],
                    [
                        'route' => 'operational-alerts.reminders',
                        'label' => 'Recordatorios',
                        'icon' => 'fas fa-clock',
                        'match' => ['operational-alerts.reminders'],
                    ],
                    [
                        'route' => 'performance-metrics.index',
                        'label' => 'Indicadores',
                        'icon' => 'fas fa-chart-line',
                        'match' => ['performance-metrics.*'],
                    ],
                    [
                        'route' => 'tasks.index',
                        'label' => 'Tareas',
                        'icon' => 'fas fa-check-square',
                        'match' => ['tasks.*'],
                    ],
                    [
                        'route' => 'technician-schedule.index',
                        'label' => 'Calendario',
                        'icon' => 'fas fa-calendar-alt',
                        'match' => ['technician-schedule.index'],
                    ],
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'Reportes',
                'icon' => 'fas fa-chart-bar',
                'type' => 'dropdown',
                'match' => ['reports.*'],
                'links' => [
                    [
                        'route' => 'reports.index',
                        'label' => 'Dashboard de Reportes',
                        'icon' => 'fas fa-chart-pie',
                        'match' => ['reports.index'],
                    ],
                    [
                        'route' => 'reports.obligaciones.index',
                        'label' => 'Reporte de Obligaciones',
                        'icon' => 'fas fa-file-contract',
                        'match' => ['reports.obligaciones.*'],
                    ],
                    [
                        'route' => 'reports.services-sla.index',
                        'label' => 'Servicios y SLA',
                        'icon' => 'fas fa-chart-line',
                        'match' => ['reports.services-sla.*'],
                    ],
                    [
                        'route' => 'reports.operational-overview.index',
                        'label' => 'Panorama Operativo',
                        'icon' => 'fas fa-chart-pie',
                        'match' => ['reports.operational-overview.*'],
                    ],
                    [
                        'route' => 'reports.timeline.index',
                        'label' => 'Línea de Tiempo',
                        'icon' => 'fas fa-clock',
                        'match' => ['reports.timeline.*'],
                    ],
                    [
                        'route' => 'reports.search-analysis.index',
                        'label' => 'Búsqueda y Análisis',
                        'icon' => 'fas fa-search',
                        'match' => ['reports.search-analysis.*'],
                    ],
                    [
                        'route' => 'reports.time-range.index',
                        'label' => 'Reporte por Rango',
                        'icon' => 'fas fa-calendar-days',
                        'match' => ['reports.time-range.*'],
                    ],
                    [
                        'route' => 'reports.cuts.index',
                        'label' => 'Cortes',
                        'icon' => 'fas fa-layer-group',
                        'match' => ['reports.cuts.*'],
                    ],
                    [
                        'route' => 'reports.cuts.create',
                        'label' => 'Crear Corte',
                        'icon' => 'fas fa-plus',
                        'match' => ['reports.cuts.create'],
                    ],
                ],
            ],
            [
                'key' => 'catalogs',
                'label' => 'Configuración',
                'icon' => 'fas fa-cog',
                'type' => 'dropdown',
                'match' => ['requester-management.*', 'companies.*', 'service-families.*', 'services.*', 'sub-services.*', 'slas.*', 'users.*'],
                'links' => [
                    [
                        'route' => 'users.index',
                        'label' => 'Usuarios',
                        'icon' => 'fas fa-user',
                        'match' => ['users.*'],
                    ],
                    [
                        'route' => 'requester-management.requesters.index',
                        'label' => 'Solicitantes',
                        'icon' => 'fas fa-users',
                        'match' => ['requester-management.*'],
                    ],
                    [
                        'route' => 'requester-management.departments.index',
                        'label' => 'Departamentos',
                        'icon' => 'fas fa-sitemap',
                        'match' => ['requester-management.departments.*'],
                    ],
                    [
                        'route' => 'companies.index',
                        'label' => 'Entidades',
                        'icon' => 'fas fa-building',
                        'match' => ['companies.*'],
                    ],
                    [
                        'route' => 'contracts.index',
                        'label' => 'Contratos',
                        'icon' => 'fas fa-file-contract',
                        'match' => ['contracts.*'],
                    ],
                    [
                        'route' => 'service-families.index',
                        'label' => 'Familias',
                        'icon' => 'fas fa-layer-group',
                        'match' => ['service-families.*'],
                    ],
                    [
                        'route' => 'services.index',
                        'label' => 'Servicios',
                        'icon' => 'fas fa-cog',
                        'match' => ['services.*'],
                    ],
                    [
                        'route' => 'sub-services.index',
                        'label' => 'Sub-Servicios',
                        'icon' => 'fas fa-cogs',
                        'match' => ['sub-services.*'],
                    ],
                    [
                        'route' => 'slas.index',
                        'label' => 'SLAs',
                        'icon' => 'fas fa-clock',
                        'match' => ['slas.*'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Closure para resolver el estado activo de una sección/link a partir de
     * sus patrones de ruta. Idéntico al que existía inline en el layout.
     */
    private function activeResolver(): \Closure
    {
        return function ($patterns) {
            if (empty($patterns)) {
                return false;
            }

            return request()->routeIs(...(array) $patterns);
        };
    }
}
