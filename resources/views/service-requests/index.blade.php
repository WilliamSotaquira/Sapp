@extends('layouts.app')

@section('title', 'Solicitudes de Servicio')

@section('breadcrumb')
<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Solicitudes de Servicio</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-2xl overflow-hidden mb-8">
    <div class="px-8 py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-tasks text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Solicitudes de Servicio</h1>
                    <p class="text-blue-100 opacity-90 mt-1">Gestión y seguimiento de todas las solicitudes del sistema</p>
                </div>
            </div>
            <div class="bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm">
                <span class="text-sm font-semibold flex items-center">
                    <i class="fas fa-filter mr-2"></i>
                    Filtros Activos
                </span>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
    <!-- Fila 1: Estadísticas y Acción Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Tarjeta 1: Total -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-inbox text-blue-600 mr-3"></i>
                    Total
                </h3>
            </div>
            <div class="p-6 text-center">
                <div class="text-3xl font-bold text-gray-800 mb-2">{{ $serviceRequests->total() }}</div>
                <p class="text-sm text-gray-600">Solicitudes en sistema</p>
            </div>
        </div>

        <!-- Tarjeta 2: Pendientes -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-50 to-amber-50 px-6 py-4 border-b border-yellow-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-clock text-yellow-600 mr-3"></i>
                    Pendientes
                </h3>
            </div>
            <div class="p-6 text-center">
                <div class="text-3xl font-bold text-gray-800 mb-2">{{ $pendingCount ?? 0 }}</div>
                <p class="text-sm text-gray-600">Por atender</p>
            </div>
        </div>

        <!-- Tarjeta 3: Críticas -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-red-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                    Críticas
                </h3>
            </div>
            <div class="p-6 text-center">
                <div class="text-3xl font-bold text-gray-800 mb-2">{{ $criticalCount ?? 0 }}</div>
                <p class="text-sm text-gray-600">Alta prioridad</p>
            </div>
        </div>

        <!-- Tarjeta 4: Resueltas -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    Resueltas
                </h3>
            </div>
            <div class="p-6 text-center">
                <div class="text-3xl font-bold text-gray-800 mb-2">{{ $resolvedCount ?? 0 }}</div>
                <p class="text-sm text-gray-600">Completadas</p>
            </div>
        </div>

        <!-- Tarjeta 5: Nueva Solicitud -->
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 text-center text-white h-full flex flex-col justify-center">
                <div class="mb-4">
                    <i class="fas fa-plus-circle text-3xl text-white/80"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">Nueva Solicitud</h3>
                <p class="text-blue-100 text-sm mb-4">Crear una nueva solicitud de servicio</p>
                <a href="{{ route('service-requests.create') }}"
                   class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition duration-200 font-semibold inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>
                    Crear
                </a>
            </div>
        </div>
    </div>

    <!-- Fila 2: Filtros y Búsqueda -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-filter text-gray-600 mr-3"></i>
                Filtros y Búsqueda
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Filtro de Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2"></i>Estado
                    </label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="ACEPTADA">Aceptada</option>
                        <option value="EN_PROCESO">En Proceso</option>
                        <option value="PAUSADA">Pausada</option>
                        <option value="RESUELTA">Resuelta</option>
                        <option value="CERRADA">Cerrada</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>
                </div>

                <!-- Filtro de Criticidad -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-flag mr-2"></i>Nivel de Criticidad
                    </label>
                    <select id="criticalityFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas las criticidades</option>
                        <option value="BAJA">Baja</option>
                        <option value="MEDIA">Media</option>
                        <option value="ALTA">Alta</option>
                        <option value="CRITICA">Crítica</option>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-end space-x-3">
                    <button id="clearFilters" class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition duration-200 font-medium text-sm">
                        <i class="fas fa-times mr-2"></i>Limpiar
                    </button>
                    <button id="applyFilters" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 font-medium text-sm">
                        <i class="fas fa-check mr-2"></i>Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila 3: Lista de Solicitudes -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
            <h3 class="text-lg font-bold text-gray-800 flex items-center justify-between">
                <span class="flex items-center">
                    <i class="fas fa-list text-blue-600 mr-3"></i>
                    Lista de Solicitudes
                </span>
                <span class="text-sm font-normal text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                    {{ $serviceRequests->total() }} resultados
                </span>
            </h3>
        </div>
        <div class="p-6">
            @if($serviceRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título y Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($serviceRequests as $request)
                        <tr class="hover:bg-gray-50 transition duration-150"
                            data-status="{{ $request->status }}"
                            data-criticality="{{ $request->criticality_level }}">

                            <!-- Ticket -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('service-requests.show', $request) }}"
                                   class="font-mono text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                    #{{ $request->ticket_number }}
                                </a>
                            </td>

                            <!-- Título y Descripción -->
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ Str::limit($request->title, 50) }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($request->description, 70) }}</div>
                            </td>

                            <!-- Servicio -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $request->subService->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $request->subService->service->family->name ?? 'N/A' }}</div>
                            </td>

                            <!-- Prioridad -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                $criticalityColors = [
                                    'BAJA' => 'bg-green-100 text-green-800 border-green-200',
                                    'MEDIA' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'ALTA' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'CRITICA' => 'bg-red-100 text-red-800 border-red-200'
                                ];
                                @endphp
                                <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $criticalityColors[$request->criticality_level] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    <i class="fas fa-flag mr-1"></i>{{ $request->criticality_level }}
                                </span>
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                $statusColors = [
                                    'PENDIENTE' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'ACEPTADA' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'EN_PROCESO' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'PAUSADA' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'RESUELTA' => 'bg-green-100 text-green-800 border-green-200',
                                    'CERRADA' => 'bg-gray-100 text-gray-800 border-gray-200',
                                    'CANCELADA' => 'bg-red-100 text-red-800 border-red-200'
                                ];
                                $statusIcons = [
                                    'PENDIENTE' => 'fa-clock',
                                    'ACEPTADA' => 'fa-check',
                                    'EN_PROCESO' => 'fa-cog',
                                    'PAUSADA' => 'fa-pause',
                                    'RESUELTA' => 'fa-check-double',
                                    'CERRADA' => 'fa-lock',
                                    'CANCELADA' => 'fa-times'
                                ];
                                @endphp
                                <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    <i class="fas {{ $statusIcons[$request->status] ?? 'fa-circle' }} mr-1"></i>
                                    {{ $request->status }}
                                </span>
                            </td>

                            <!-- Solicitante -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ substr($request->requester->name ?? 'N', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-900">{{ $request->requester->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $request->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $request->created_at->format('H:i') }}</div>
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('service-requests.show', $request) }}"
                                        class="text-blue-600 hover:text-blue-900 transition duration-150 p-2 rounded-lg bg-blue-50 hover:bg-blue-100"
                                        title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if(in_array($request->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA']))
                                    <a href="{{ route('service-requests.edit', $request) }}"
                                        class="text-green-600 hover:text-green-900 transition duration-150 p-2 rounded-lg bg-green-50 hover:bg-green-100"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif

                                    @if(in_array($request->status, ['PENDIENTE', 'CANCELADA']))
                                    <form action="{{ route('service-requests.destroy', $request) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-900 transition duration-150 p-2 rounded-lg bg-red-50 hover:bg-red-100"
                                            title="Eliminar"
                                            onclick="return confirm('¿Está seguro de que desea eliminar esta solicitud?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <!-- Estado vacío -->
            <div class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-inbox text-6xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No se encontraron solicitudes</h3>
                <p class="text-gray-500 mb-6">No hay solicitudes que coincidan con los criterios actuales.</p>
                <a href="{{ route('service-requests.create') }}"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 inline-flex items-center font-semibold">
                    <i class="fas fa-plus mr-2"></i>Crear la primera solicitud
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Fila 4: Paginación -->
    @if($serviceRequests->hasPages())
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4">
            {{ $serviceRequests->links() }}
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos del DOM
        const statusFilter = document.getElementById('statusFilter');
        const criticalityFilter = document.getElementById('criticalityFilter');
        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');

        // Aplicar filtros
        function applyFilters() {
            const status = statusFilter.value;
            const criticality = criticalityFilter.value;
            const rows = document.querySelectorAll('tbody tr[data-status]');

            let visibleCount = 0;

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowCriticality = row.getAttribute('data-criticality');

                const statusMatch = !status || rowStatus === status;
                const criticalityMatch = !criticality || rowCriticality === criticality;

                if (statusMatch && criticalityMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Actualizar contador
            const counter = document.querySelector('.bg-blue-100');
            if (counter) {
                counter.textContent = `${visibleCount} resultados`;
            }
        }

        // Limpiar filtros
        function clearFilters() {
            statusFilter.value = '';
            criticalityFilter.value = '';
            applyFilters();
            sessionStorage.removeItem('serviceRequests_statusFilter');
            sessionStorage.removeItem('serviceRequests_criticalityFilter');
        }

        // Event Listeners
        applyFiltersBtn.addEventListener('click', applyFilters);
        clearFiltersBtn.addEventListener('click', clearFilters);

        // Restaurar valores de filtro si existen
        const savedStatus = sessionStorage.getItem('serviceRequests_statusFilter');
        const savedCriticality = sessionStorage.getItem('serviceRequests_criticalityFilter');

        if (savedStatus) statusFilter.value = savedStatus;
        if (savedCriticality) criticalityFilter.value = savedCriticality;

        // Guardar valores de filtro
        statusFilter.addEventListener('change', () => {
            sessionStorage.setItem('serviceRequests_statusFilter', statusFilter.value);
        });

        criticalityFilter.addEventListener('change', () => {
            sessionStorage.setItem('serviceRequests_criticalityFilter', criticalityFilter.value);
        });

        // Aplicar filtros al cargar la página
        applyFilters();
    });
</script>
@endpush

<style>
    .bg-gradient-to-br {
        background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
    }

    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
    }

    .transition {
        transition: all 0.2s ease-in-out;
    }

    .hover\:bg-gray-50:hover {
        background-color: rgba(249, 250, 251, 0.8);
    }

    .font-mono {
        font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, monospace;
    }
</style>
