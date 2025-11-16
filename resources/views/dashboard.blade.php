@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Principal</h1>
            <div class="mt-2 sm:mt-0">
                <span class="text-sm text-gray-500">Última actualización: {{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Estadísticas Principales con animación de carga -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @php
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
        @endphp

        @foreach($stats as $stat)
        <a href="{{ $stat['route'] }}" class="block transform transition-transform hover:scale-105">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-{{ $stat['color'] }}-500 h-full">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 bg-{{ $stat['color'] }}-100 rounded-lg">
                            <i class="{{ $stat['icon'] }} text-{{ $stat['color'] }}-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">{{ $stat['title'] }}</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stat['count'] }}</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-{{ $stat['color'] }}-400 text-sm"></i>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    <!-- Dos Columnas: Acciones Rápidas y Resumen de Solicitudes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Acciones Rápidas mejoradas -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Acciones Rápidas</h2>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Acceso directo</span>
            </div>
            <div class="space-y-3">
                @php
                    $quickActions = [
                        [
                            'title' => 'Nueva Solicitud',
                            'description' => 'Crear una nueva solicitud de servicio',
                            'route' => route('service-requests.create'),
                            'color' => 'blue',
                            'icon' => 'fas fa-plus-circle'
                        ],
                        [
                            'title' => 'Nueva Familia',
                            'description' => 'Agregar familia de servicio',
                            'route' => route('service-families.create'),
                            'color' => 'green',
                            'icon' => 'fas fa-layer-group'
                        ],
                        [
                            'title' => 'Ver Solicitudes',
                            'description' => 'Gestionar todas las solicitudes',
                            'route' => route('service-requests.index'),
                            'color' => 'purple',
                            'icon' => 'fas fa-list'
                        ],
                        [
                            'title' => 'Gestionar SLAs',
                            'description' => 'Configurar acuerdos de nivel de servicio',
                            'route' => route('slas.index'),
                            'color' => 'orange',
                            'icon' => 'fas fa-handshake'
                        ]
                    ];
                @endphp

                @foreach($quickActions as $action)
                <a href="{{ $action['route'] }}" class="flex items-center p-2.5 sm:p-3 bg-{{ $action['color'] }}-50 rounded-lg hover:bg-{{ $action['color'] }}-100 transition-all duration-200 border border-{{ $action['color'] }}-200 group">
                    <div class="p-1.5 sm:p-2 bg-{{ $action['color'] }}-100 rounded-lg group-hover:scale-110 transition-transform duration-200 flex-shrink-0">
                        <i class="{{ $action['icon'] }} text-{{ $action['color'] }}-600 text-base sm:text-lg"></i>
                    </div>
                    <div class="ml-3 sm:ml-4 flex-1 min-w-0">
                        <p class="font-medium text-{{ $action['color'] }}-800 text-sm sm:text-base truncate">{{ $action['title'] }}</p>
                        <p class="text-xs sm:text-sm text-{{ $action['color'] }}-600 hidden sm:block">{{ $action['description'] }}</p>
                    </div>
                    <i class="fas fa-arrow-right text-{{ $action['color'] }}-400 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all duration-200 text-xs sm:text-sm flex-shrink-0"></i>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Resumen de Solicitudes por Estado con gráfico visual -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 text-gray-800">Resumen de Solicitudes</h2>

            <!-- Mini gráfico de barras -->
            @php
                $statuses = [
                    'PENDIENTE' => ['color' => 'yellow', 'icon' => 'fas fa-clock', 'count' => \App\Models\ServiceRequest::where('status', 'PENDIENTE')->count()],
                    'ACEPTADA' => ['color' => 'blue', 'icon' => 'fas fa-check-circle', 'count' => \App\Models\ServiceRequest::where('status', 'ACEPTADA')->count()],
                    'EN_PROCESO' => ['color' => 'purple', 'icon' => 'fas fa-play-circle', 'count' => \App\Models\ServiceRequest::where('status', 'EN_PROCESO')->count()],
                    'PAUSADA' => ['color' => 'orange', 'icon' => 'fas fa-pause-circle', 'count' => \App\Models\ServiceRequest::where('status', 'PAUSADA')->count()],
                    'RESUELTA' => ['color' => 'green', 'icon' => 'fas fa-check-double', 'count' => \App\Models\ServiceRequest::where('status', 'RESUELTA')->count()],
                ];

                $totalRequests = \App\Models\ServiceRequest::count();
            @endphp

            @if($totalRequests > 0)
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Distribución por estado</h3>
                <div class="flex h-4 bg-gray-200 rounded-full overflow-hidden">
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
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($statuses as $status => $data)
                        @if($data['count'] > 0)
                            <div class="flex items-center text-xs">
                                <div class="w-3 h-3 bg-{{ $data['color'] }}-500 rounded mr-1"></div>
                                <span class="text-gray-600">{{ $status }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Lista de estados -->
            <div class="space-y-4">
                @foreach($statuses as $status => $data)
                <div class="flex items-center justify-between p-3 bg-{{ $data['color'] }}-50 rounded-lg border border-{{ $data['color'] }}-200 hover:bg-{{ $data['color'] }}-100 transition-colors">
                    <div class="flex items-center">
                        <i class="{{ $data['icon'] }} text-{{ $data['color'] }}-600 text-lg mr-3"></i>
                        <span class="font-medium text-{{ $data['color'] }}-800">{{ $status }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-2xl font-bold text-{{ $data['color'] }}-600 mr-2">{{ $data['count'] }}</span>
                        @if($totalRequests > 0)
                            <span class="text-sm text-{{ $data['color'] }}-500">
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
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800 mb-2 sm:mb-0">Solicitudes Recientes</h2>
            <div class="flex space-x-2">
                <div class="relative">
                    <input
                        type="text"
                        id="search-requests"
                        placeholder="Buscar solicitud..."
                        class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 w-full sm:w-48"
                    >
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                <select id="filter-status" class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos los estados</option>
                    @foreach(array_keys($statuses) as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @php
            $recentRequests = \App\Models\ServiceRequest::with(['subService.service.family', 'requester'])
                ->latest()
                ->take(8)
                ->get();
        @endphp

        @if($recentRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full" id="recent-requests-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="ticket">
                                Ticket <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="title">
                                Título <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="status">
                                Estado <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sortable" data-sort="date">
                                Fecha <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentRequests as $request)
                            <tr class="hover:bg-gray-50 request-row" data-status="{{ $request->status }}" data-ticket="{{ $request->ticket_number }}" data-title="{{ strtolower($request->title) }}">
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
                <div class="text-sm text-gray-500">
                    Mostrando <span class="font-medium">{{ $recentRequests->count() }}</span> de <span class="font-medium">{{ \App\Models\ServiceRequest::count() }}</span> solicitudes
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

<!-- Script para funcionalidades de UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrado de solicitudes
    const searchInput = document.getElementById('search-requests');
    const statusFilter = document.getElementById('filter-status');
    const requestRows = document.querySelectorAll('.request-row');

    function filterRequests() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        requestRows.forEach(row => {
            const ticket = row.getAttribute('data-ticket');
            const title = row.getAttribute('data-title');
            const status = row.getAttribute('data-status');

            const matchesSearch = ticket.includes(searchTerm) || title.includes(searchTerm);
            const matchesStatus = statusValue === '' || status === statusValue;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterRequests);
    statusFilter.addEventListener('change', filterRequests);

    // Ordenamiento de columnas (simplificado para demostración)
    const sortableHeaders = document.querySelectorAll('.sortable');

    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortBy = this.getAttribute('data-sort');
            alert(`Funcionalidad de ordenamiento por ${sortBy} - Esta funcionalidad requeriría implementación completa con JavaScript`);
            // Aquí iría la lógica completa de ordenamiento de la tabla
        });
    });
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
</style>
@endsection
