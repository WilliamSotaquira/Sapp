@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" role="main">
    <a href="#recent-requests-heading" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 bg-blue-600 text-white px-3 py-1 rounded">Saltar a solicitudes recientes</a>
    <!-- Encabezado con breadcrumb y título -->
    <div class="mb-8">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="{{ url('/') }}" class="text-gray-500 hover:text-gray-700">Inicio</a></li>
                <li class="flex items-center">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="ml-2 text-gray-700 font-medium">Dashboard</span>
                </li>
            </ol>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Dashboard Principal</h1>
            <div class="mt-2 sm:mt-0">
                <span class="text-xs sm:text-sm text-gray-500">Última actualización: {{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Estadísticas Principales (ideal pasar $stats desde controlador y cachear resultados) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @php
            // Si no se pasa $stats desde el controlador, se define aquí (fallback).
            if(!isset($stats)) {
                $stats = [
                    [
                        'title' => 'Familias de Servicio',
                        'count' => \App\Models\ServiceFamily::count(),
                        'color' => 'blue',
                        'icon' => 'fas fa-layer-group',
                        'route' => route('service-families.index')
                    ],
                    [
                        'title' => 'Servicios',
                        'count' => \App\Models\Service::count(),
                        'color' => 'green',
                        'icon' => 'fas fa-cogs',
                        'route' => route('services.index')
                    ],
                    [
                        'title' => 'Sub-Servicios',
                        'count' => \App\Models\SubService::count(),
                        'color' => 'purple',
                        'icon' => 'fas fa-list-alt',
                        'route' => route('sub-services.index')
                    ],
                    [
                        'title' => 'Total Solicitudes',
                        'count' => \App\Models\ServiceRequest::count(),
                        'color' => 'orange',
                        'icon' => 'fas fa-tasks',
                        'route' => route('service-requests.index')
                    ]
                ];
            }
        @endphp

        @foreach($stats as $stat)
        <a href="{{ $stat['route'] }}" class="block transform transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-{{ $stat['color'] }}-500 rounded-lg" aria-label="Ir a {{ $stat['title'] }}">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-{{ $stat['color'] }}-500 h-full relative">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 bg-{{ $stat['color'] }}-100 rounded-lg">
                            <i class="{{ $stat['icon'] }} text-{{ $stat['color'] }}-600 text-lg" aria-hidden="true"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs sm:text-sm font-medium text-gray-600">{{ $stat['title'] }}</p>
                            <p class="text-xl sm:text-2xl font-semibold text-gray-900 count-up" data-count="{{ $stat['count'] }}">{{ $stat['count'] }}</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-{{ $stat['color'] }}-400 text-sm" aria-hidden="true"></i>
                </div>
                <span class="absolute top-2 right-2 text-xs text-gray-400" aria-hidden="true">→</span>
            </div>
        </a>
        @endforeach
    </div>

    <!-- Dos Columnas: Acciones Rápidas y Resumen de Solicitudes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Acciones Rápidas mejoradas -->
        <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Productividad</p>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Acciones Rápidas</h2>
                </div>
                <span class="text-xs font-semibold bg-blue-50 text-blue-700 px-3 py-1 rounded-full">Acceso directo</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @php
                    $quickActions = [
                        [
                            'title' => 'Nueva Solicitud',
                            'description' => 'Crear una nueva solicitud de servicio',
                            'route' => route('service-requests.create'),
                            'color' => 'blue',
                            'icon' => 'fas fa-plus-circle',
                            'badge' => 'Alta'
                        ],
                        [
                            'title' => 'Nueva Familia',
                            'description' => 'Agregar familia de servicio',
                            'route' => route('service-families.create'),
                            'color' => 'green',
                            'icon' => 'fas fa-layer-group',
                            'badge' => 'Catálogo'
                        ],
                        [
                            'title' => 'Ver Solicitudes',
                            'description' => 'Gestionar todas las solicitudes',
                            'route' => route('service-requests.index'),
                            'color' => 'purple',
                            'icon' => 'fas fa-list',
                            'badge' => 'Operación'
                        ],
                        [
                            'title' => 'Gestionar SLAs',
                            'description' => 'Configurar acuerdos de nivel de servicio',
                            'route' => route('slas.index'),
                            'color' => 'orange',
                            'icon' => 'fas fa-handshake',
                            'badge' => 'Compromisos'
                        ]
                    ];
                @endphp

                @foreach($quickActions as $action)
                    <a href="{{ $action['route'] }}" aria-label="{{ $action['title'] }}"
                        class="group rounded-2xl border border-{{ $action['color'] }}-100/70 bg-gradient-to-br from-white via-white to-{{ $action['color'] }}-50/40 p-4 shadow-sm hover:shadow-{{ $action['color'] }}-200/80 transition-all duration-200">
                        <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-wide text-{{ $action['color'] }}-600">
                            <span>{{ $action['badge'] }}</span>
                            <span class="inline-flex items-center text-{{ $action['color'] }}-500">
                                <span class="mr-1">Ir</span>
                                <i class="fas fa-chevron-right text-xs group-hover:translate-x-1 transition-transform duration-200"></i>
                            </span>
                        </div>
                        <div class="mt-2.5 flex items-start space-x-3">
                            <div class="p-2.5 rounded-xl bg-{{ $action['color'] }}-100 text-{{ $action['color'] }}-600 shadow-inner shadow-white/60 group-hover:scale-110 transition-transform duration-200">
                                <i class="{{ $action['icon'] }} text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-{{ $action['color'] }}-900 text-sm leading-tight truncate">
                                    {{ $action['title'] }}
                                </p>
                                <p class="text-xs text-{{ $action['color'] }}-600 mt-1">
                                    {{ $action['description'] }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs text-{{ $action['color'] }}-600">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-clock text-[10px]"></i>
                                Disponible 24/7
                            </span>
                            <span class="flex items-center gap-1 group-hover:text-{{ $action['color'] }}-800 transition-colors duration-200">
                                <i class="fas fa-arrow-right text-[10px]"></i>
                                Acceder
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Resumen de Solicitudes por Estado (pasar $statuses y $totalRequests desde controlador para optimizar) -->
        <div class="bg-white rounded-2xl shadow p-4 sm:p-5 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-widest text-gray-400">Visión general</p>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Resumen de Solicitudes</h2>
                </div>
                <span class="text-xs font-semibold text-gray-500">Total: {{ $totalRequests ?? 0 }}</span>
            </div>

            <!-- Mini gráfico de barras -->
            @php
                if(!isset($statuses)) {
                    $statuses = [
                        'PENDIENTE' => ['color' => 'yellow', 'icon' => 'fas fa-clock', 'count' => \App\Models\ServiceRequest::where('status', 'PENDIENTE')->count()],
                        'ACEPTADA' => ['color' => 'blue', 'icon' => 'fas fa-check-circle', 'count' => \App\Models\ServiceRequest::where('status', 'ACEPTADA')->count()],
                        'EN_PROCESO' => ['color' => 'purple', 'icon' => 'fas fa-play-circle', 'count' => \App\Models\ServiceRequest::where('status', 'EN_PROCESO')->count()],
                        'PAUSADA' => ['color' => 'orange', 'icon' => 'fas fa-pause-circle', 'count' => \App\Models\ServiceRequest::where('status', 'PAUSADA')->count()],
                        'RESUELTA' => ['color' => 'green', 'icon' => 'fas fa-check-double', 'count' => \App\Models\ServiceRequest::where('status', 'RESUELTA')->count()],
                        'CERRADA' => ['color' => 'gray', 'icon' => 'fas fa-lock', 'count' => \App\Models\ServiceRequest::where('status', 'CERRADA')->count()],
                    ];
                }
                if(!isset($totalRequests)) {
                    $totalRequests = \App\Models\ServiceRequest::count();
                }
            @endphp

            @if($totalRequests > 0)
            <div class="mb-4 bg-gray-50 border border-gray-100 p-2.5 rounded-2xl">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Distribución por estado</h3>
                    <span class="text-xs text-gray-500">Proporción %</span>
                </div>
                <div class="flex h-2 sm:h-2.5 bg-gray-200 rounded-full overflow-hidden">
                    @foreach($statuses as $status => $data)
                        @if($data['count'] > 0)
                            <div
                                class="h-full bg-{{ $data['color'] }}-500"
                                style="width: {{ ($data['count'] / $totalRequests) * 100 }}%"
                                title="{{ $status }}: {{ $data['count'] }} ({{ round(($data['count'] / $totalRequests) * 100, 1) }}%)"
                            ></div>
                        @endif
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-x-2 gap-y-1 mt-2">
                    @foreach($statuses as $status => $data)
                        @if($data['count'] > 0)
                            <div class="flex items-center text-[10px] text-gray-600">
                                <div class="w-2 h-2 bg-{{ $data['color'] }}-500 rounded mr-1"></div>
                                <span>{{ $status }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Lista de estados -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($statuses as $status => $data)
                <div class="flex items-center justify-between p-2.5 rounded-2xl border border-{{ $data['color'] }}-100 bg-{{ $data['color'] }}-50/40 shadow hover:-translate-y-0.5 transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-xl bg-white/70 text-{{ $data['color'] }}-600 mr-3">
                            <i class="{{ $data['icon'] }} text-sm"></i>
                        </div>
                        <div>
                            <span class="font-semibold text-{{ $data['color'] }}-900 text-xs sm:text-sm">{{ $status }}</span>
                            <p class="text-[10px] text-{{ $data['color'] }}-600/80">Estado operativo</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-lg font-semibold text-{{ $data['color'] }}-800 mr-1.5">{{ $data['count'] }}</span>
                        @if($totalRequests > 0)
                            <span class="text-xs text-{{ $data['color'] }}-600/80">
                                ({{ round(($data['count'] / $totalRequests) * 100, 1) }}%)
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Solicitudes Recientes con filtros y búsqueda -->
    <div class="bg-white rounded-2xl shadow" aria-labelledby="recent-requests-heading">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between">
            <h2 id="recent-requests-heading" class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">Solicitudes Recientes</h2>
            <div class="flex space-x-2 items-center text-xs sm:text-sm" role="toolbar" aria-label="Herramientas de filtrado de solicitudes">
                <div class="relative">
                    <input
                        type="text"
                        id="search-requests"
                        placeholder="Buscar solicitud..."
                        aria-label="Buscar solicitud por ticket o título"
                        class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-blue-500 focus:border-blue-500 w-full sm:w-48"
                    >
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                <select id="filter-status" aria-label="Filtrar por estado" class="border border-gray-300 rounded-lg text-xs sm:text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos los estados</option>
                    @foreach(array_keys($statuses) as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
                <button id="clear-filters" type="button" class="border border-gray-300 rounded-lg text-xs sm:text-sm px-3 py-2 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500" aria-label="Limpiar filtros">Limpiar</button>
                <button id="toggle-density" type="button" class="border border-gray-300 rounded-lg text-xs sm:text-sm px-3 py-2 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500" aria-label="Alternar densidad de filas">Densidad</button>
            </div>
        </div>
        @php
            if(!isset($recentRequests)) {
                $recentRequests = \App\Models\ServiceRequest::with(['subService.service.family', 'requester'])
                    ->latest()
                    ->take(8)
                    ->get();
            }
        @endphp

        @if($recentRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full" id="recent-requests-table" role="table" aria-describedby="recent-requests-caption">
                    <caption id="recent-requests-caption" class="sr-only">Tabla con las solicitudes recientes y su estado.</caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="ticket" aria-sort="none" tabindex="0">Ticket <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="title" aria-sort="none" tabindex="0">Título <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="status" aria-sort="none" tabindex="0">Estado <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="date" aria-sort="none" tabindex="0">Fecha <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentRequests as $request)
                            <tr class="hover:bg-gray-50 request-row" data-status="{{ $request->status }}" data-ticket="{{ $request->ticket_number }}" data-title="{{ strtolower($request->title) }}" data-date="{{ $request->created_at->getTimestamp() }}" tabindex="0" role="row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('service-requests.show', $request) }}" class="font-medium text-blue-600 hover:text-blue-900 flex items-center">
                                        {{ $request->ticket_number }}
                                        @if($request->priority === 'ALTA')
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Alta
                                            </span>
                                        @elseif($request->priority === 'MEDIA')
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Media
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ Str::limit($request->title, 35) }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($request->description, 50) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <span class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: {{ $request->subService->service->family->color ?? '#6b7280' }}"></span>
                                        {{ $request->subService->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                            'PAUSADA' => 'bg-orange-100 text-orange-800',
                                            'RESUELTA' => 'bg-green-100 text-green-800',
                                            'CERRADA' => 'bg-gray-100 text-gray-800',
                                            'CANCELADA' => 'bg-red-100 text-red-800'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }} flex items-center w-fit">
                                        {{ $request->status }}
                                        @if($request->is_paused && $request->status === 'PAUSADA')
                                            <i class="fas fa-pause ml-1"></i>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $request->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                                    @if($request->created_at->diffInDays(now()) <= 1)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                            <i class="fas fa-clock mr-1"></i> Reciente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="text-sm text-gray-500" aria-live="polite">
                    Mostrando <span class="font-medium">{{ $recentRequests->count() }}</span> de <span class="font-medium">{{ $totalRequests }}</span> solicitudes
                </div>
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                    Ver todas las solicitudes
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay solicitudes</h3>
                <p class="text-gray-500 mb-4">Aún no se han creado solicitudes de servicio.</p>
                <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Crear Primera Solicitud
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Script para funcionalidades de UX mejoradas (filtrado + ordenamiento accesible + animaciones + persistencia) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-requests');
    const statusFilter = document.getElementById('filter-status');
    const table = document.getElementById('recent-requests-table');
    const tbody = table.querySelector('tbody');
    let rows = Array.from(tbody.querySelectorAll('.request-row'));
    const clearBtn = document.getElementById('clear-filters');
    const densityBtn = document.getElementById('toggle-density');
    const STORAGE_KEY = 'dashboard_filters_v1';
    const densityClass = 'dense-rows';

    // Restaurar filtros
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if(stored) {
            if(stored.search) searchInput.value = stored.search;
            if(stored.status) statusFilter.value = stored.status;
            if(stored.density) document.getElementById('recent-requests-table').classList.add(densityClass);
        }
    } catch(e) {}

    function filterRequests() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        rows.forEach(row => {
            const ticket = row.dataset.ticket.toLowerCase();
            const title = row.dataset.title;
            const status = row.dataset.status;
            const matchesSearch = ticket.includes(searchTerm) || title.includes(searchTerm);
            const matchesStatus = statusValue === '' || status === statusValue;
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
        persistState();
    }

    searchInput.addEventListener('input', filterRequests);
    statusFilter.addEventListener('change', filterRequests);

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        statusFilter.value = '';
        filterRequests();
    });

    densityBtn.addEventListener('click', () => {
        table.classList.toggle(densityClass);
        persistState();
    });

    const headers = table.querySelectorAll('.sortable');
    let currentSort = { key: null, direction: 'asc' };

    function sortRows(key) {
        const direction = (currentSort.key === key && currentSort.direction === 'asc') ? 'desc' : 'asc';
        currentSort = { key, direction };

        headers.forEach(h => h.setAttribute('aria-sort', h.dataset.sort === key ? direction === 'asc' ? 'ascending' : 'descending' : 'none'));

        const multiplier = direction === 'asc' ? 1 : -1;
        rows.sort((a, b) => {
            let aVal, bVal;
            switch(key) {
                case 'ticket':
                    aVal = a.dataset.ticket.toLowerCase();
                    bVal = b.dataset.ticket.toLowerCase();
                    break;
                case 'title':
                    aVal = a.dataset.title;
                    bVal = b.dataset.title;
                    break;
                case 'status':
                    aVal = a.dataset.status;
                    bVal = b.dataset.status;
                    break;
                case 'date':
                    aVal = parseInt(a.dataset.date, 10);
                    bVal = parseInt(b.dataset.date, 10);
                    break;
                default:
                    aVal = '';
                    bVal = '';
            }
            if (aVal < bVal) return -1 * multiplier;
            if (aVal > bVal) return 1 * multiplier;
            return 0;
        });
        // Reinsertar manteniendo sólo filas visibles (para no romper filtro)
        const fragment = document.createDocumentFragment();
        rows.forEach(r => fragment.appendChild(r));
        tbody.appendChild(fragment);
    }

    headers.forEach(header => {
        header.addEventListener('click', () => sortRows(header.dataset.sort));
        header.addEventListener('keydown', (e) => {
            if(e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                sortRows(header.dataset.sort);
            }
        });
    });

    // Animación de contadores (IntersectionObserver)
    const counters = document.querySelectorAll('.count-up');
    const animateCounter = el => {
        const target = parseInt(el.dataset.count, 10);
        const duration = 800;
        const start = performance.now();
        const initial = 0;
        function step(ts) {
            const progress = Math.min((ts - start) / duration, 1);
            const value = Math.floor(progress * (target - initial) + initial);
            el.textContent = value.toLocaleString('es-ES');
            if(progress < 1) requestAnimationFrame(step); else el.textContent = target.toLocaleString('es-ES');
        }
        requestAnimationFrame(step);
    };
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });
    counters.forEach(c => observer.observe(c));

    function persistState() {
        const state = {
            search: searchInput.value,
            status: statusFilter.value,
            density: table.classList.contains(densityClass)
        };
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch(e) {}
    }
    // Aplicar filtrado inicial (por si restauró valores)
    filterRequests();
});
</script>

<style>
/* Mejoras visuales adicionales */
.sortable:hover {
    background-color: #f9fafb;
}

.request-row {
    transition: background-color 0.2s ease;
}

/* Asegurar que los colores de Tailwind se muestren correctamente */
.bg-yellow-50 { background-color: #fefce8; }
.bg-blue-50 { background-color: #eff6ff; }
.bg-purple-50 { background-color: #faf5ff; }
.bg-orange-50 { background-color: #fff7ed; }
.bg-green-50 { background-color: #f0fdf4; }
.sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
.sortable:focus { outline:2px solid #3b82f6; outline-offset:2px; }
.dense-rows tbody tr td { padding-top:0.35rem; padding-bottom:0.35rem; }
.request-row:focus { background-color:#f0f9ff; box-shadow:inset 0 0 0 2px #3b82f6; }
</style>
@endsection
