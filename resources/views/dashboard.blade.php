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

    <div class="bg-white rounded-2xl shadow p-6 border border-gray-100 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-gray-400">Productividad</p>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Acciones Rápidas</h2>
            </div>
            <span class="text-xs font-semibold bg-blue-50 text-blue-700 px-3 py-1 rounded-full">Acceso directo</span>
        </div>
        <div class="grid grid-flow-col auto-cols-[minmax(180px,1fr)] gap-3 overflow-x-auto pb-2 text-sm">
            <a href="{{ route('service-requests.create') }}" class="flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50/50 px-4 py-3 hover:bg-blue-50 transition">
                <div class="p-2 rounded-lg bg-white text-blue-600 shadow-inner">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-blue-900 truncate">Nueva Solicitud</p>
                    <span class="text-xs text-blue-700">Alta inmediata</span>
                </div>
                <i class="fas fa-arrow-right text-blue-500 ml-auto"></i>
            </a>
            <a href="{{ route('service-families.create') }}" class="flex items-center gap-3 rounded-xl border border-green-100 bg-green-50/50 px-4 py-3 hover:bg-green-50 transition">
                <div class="p-2 rounded-lg bg-white text-green-600 shadow-inner">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-green-900 truncate">Nueva Familia</p>
                    <span class="text-xs text-green-700">Agregar catálogo</span>
                </div>
                <i class="fas fa-arrow-right text-green-500 ml-auto"></i>
            </a>
            <a href="{{ route('service-requests.index') }}" class="flex items-center gap-3 rounded-xl border border-purple-100 bg-purple-50/50 px-4 py-3 hover:bg-purple-50 transition">
                <div class="p-2 rounded-lg bg-white text-purple-600 shadow-inner">
                    <i class="fas fa-list"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-purple-900 truncate">Ver Solicitudes</p>
                    <span class="text-xs text-purple-700">Panel operativo</span>
                </div>
                <i class="fas fa-arrow-right text-purple-500 ml-auto"></i>
            </a>
            <a href="{{ route('slas.index') }}" class="flex items-center gap-3 rounded-xl border border-orange-100 bg-orange-50/50 px-4 py-3 hover:bg-orange-50 transition">
                <div class="p-2 rounded-lg bg-white text-orange-600 shadow-inner">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-orange-900 truncate">Gestionar SLAs</p>
                    <span class="text-xs text-orange-700">Compromisos</span>
                </div>
                <i class="fas fa-arrow-right text-orange-500 ml-auto"></i>
            </a>
        </div>
    </div>

    <!-- Alertas de Tareas Críticas (solo para admin) -->
    @if(auth()->user()?->isAdmin())
        @include('partials.task-alerts')
    @endif

    <!-- Dos Columnas: Agenda y Resumen de Solicitudes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Agenda -->
        <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-400">Hoy</p>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mt-1">Mi Agenda</h2>
                </div>
                <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full">Próximos</span>
            </div>
            @php
                if(!isset($agenda)) {
                    $technicianId = auth()->user()?->technician?->id ?? null;
                    if($technicianId) {
                        $agenda = \App\Models\Task::with('serviceRequest')
                            ->where('technician_id', $technicianId)
                            ->whereDate('scheduled_date', '>=', now()->toDateString())
                            ->orderBy('scheduled_date')
                            ->orderByRaw("COALESCE(scheduled_start_time, scheduled_time, '23:59')")
                            ->take(6)
                            ->get()
                            ->map(function($task) {
                                return [
                                    'time' => $task->scheduled_start_time ?? $task->scheduled_time ?? '—',
                                    'date' => optional($task->scheduled_date)->format('d/m') ?? '',
                                    'title' => $task->title ?? 'Tarea sin título',
                                    'location' => $task->serviceRequest?->ticket_number ? 'Ticket '.$task->serviceRequest->ticket_number : 'Sin ticket',
                                    'status' => $task->status ? ucfirst(str_replace('_', ' ', $task->status)) : 'Pendiente',
                                    'url' => route('tasks.show', $task),
                                ];
                            })
                            ->toArray();
                    } else {
                        $agenda = [];
                    }
                }
                $statusColors = [
                    'Pendiente' => 'bg-amber-100 text-amber-800',
                    'Programada' => 'bg-blue-100 text-blue-800',
                    'En curso' => 'bg-green-100 text-green-800',
                ];
            @endphp
            @if(count($agenda) > 0)
                <ul class="space-y-3">
                    @foreach($agenda as $item)
                        <li>
                            <a href="{{ $item['url'] ?? '#' }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-indigo-100 hover:bg-indigo-50/40 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <div class="flex flex-col items-center justify-center w-20 rounded-lg bg-gray-50 text-gray-900 font-semibold">
                                    <span class="text-xs text-gray-500">Fecha</span>
                                    <span class="text-sm">{{ $item['date'] ?? '' }}</span>
                                    <span class="text-xs text-gray-600">{{ $item['time'] ?? '' }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-900 truncate">{{ $item['title'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $item['location'] }}</p>
                                </div>
                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $statusColors[$item['status']] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $item['status'] }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 text-right">
                    <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                        Ver calendario
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @else
                <div class="text-center py-8 text-gray-600">
                    <i class="fas fa-sun text-3xl text-amber-300 mb-3"></i>
                    <p class="font-semibold text-gray-800">Sin tareas pendientes 🎉</p>
                    <p class="text-sm text-gray-500">Aprovecha para adelantar documentación o capacitarte.</p>
                    <a href="{{ route('technician-schedule.my-agenda') }}" class="mt-3 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Abrir calendario
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @endif
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
                <a href="{{ route('service-requests.index', ['status' => $status]) }}"
                   class="flex items-center justify-between p-2.5 rounded-2xl border border-{{ $data['color'] }}-100 bg-{{ $data['color'] }}-50/40 shadow hover:-translate-y-0.5 transition-transform duration-200 focus:outline-none focus:ring-2 focus:ring-{{ $data['color'] }}-400"
                   aria-label="Ver solicitudes en estado {{ $status }}">
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
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Solicitudes Recientes con filtros y búsqueda -->
    <div class="bg-white rounded-2xl shadow" aria-labelledby="recent-requests-heading">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between">
            <h2 id="recent-requests-heading" class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">Solicitudes Recientes</h2>
            <div class="flex flex-wrap gap-2 items-center text-xs sm:text-sm" role="toolbar" aria-label="Herramientas de filtrado de solicitudes">
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
            $recentLimit = 5;
            if(!isset($recentRequests)) {
                $recentRequests = \App\Models\ServiceRequest::with(['subService.service.family', 'requester'])
                    ->latest()
                    ->take($recentLimit)
                    ->get();
            }
        @endphp

        @if($recentRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed" id="recent-requests-table" role="table" aria-describedby="recent-requests-caption">
                    <caption id="recent-requests-caption" class="sr-only">Tabla con las solicitudes recientes y su estado.</caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="ticket" aria-sort="none" tabindex="0">Ticket <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="title" aria-sort="none" tabindex="0">Título <i class="fas fa-sort ml-1 text-gray-400"></i></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-56">Servicio</th>
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
                                    @php
                                        $serviceLabel = $request->subService->name ?? 'N/A';
                                    @endphp
                                    <div class="flex items-center min-w-0" title="{{ $serviceLabel }}">
                                        <span class="truncate">{{ Str::limit($serviceLabel, 32) }}</span>
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
                                            'CANCELADA' => 'bg-red-100 text-red-800',
                                            'RECHAZADA' => 'bg-gray-100 text-gray-800'
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

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                <div class="text-sm text-gray-500" aria-live="polite">
                    Mostrando <span class="font-medium">{{ $recentRequests->count() }}</span>
                    @if(isset($recentLimit))
                        (máx. {{ $recentLimit }})
                    @endif
                    de <span class="font-medium">{{ $totalRequests }}</span> solicitudes
                </div>
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
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
