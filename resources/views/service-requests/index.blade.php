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
    <x-service-requests.index.header.main-header />

    <div class="space-y-3 md:space-y-6" id="resultsContainer">
        <!-- Fila 1: Estadísticas y Acción Principal -->
        <!-- En móvil: layout compacto, tablet: 2-3 cols, desktop: 5 cols -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 lg:gap-6">

            <!-- Tarjeta 1: Nueva Solicitud -->
            <x-service-requests.index.stats-cards.create-action />

            <!-- Tarjeta 2: Críticas -->
            <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />

            <!-- Tarjeta 3: Pendientes -->
            <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />

            <!-- Tarjeta 4: Cerradas -->
            <x-service-requests.index.stats-cards.closed-stats :count="$closedCount ?? 0" />

            <!-- Tarjeta 5: Total -->
            <x-service-requests.index.stats-cards.total-stats :count="$serviceRequests->total()" />

        </div>

        <!-- Fila 2: Filtros y Búsqueda -->
        <div class="bg-white rounded-lg border border-gray-200 p-3 sm:p-4 md:p-5 mb-4 sm:mb-6">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h3 class="text-sm sm:text-base font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-blue-500 mr-2 text-xs sm:text-sm"></i>
                    Filtros
                </h3>
            </div>

            <form id="filtersForm" method="GET" action="{{ route('service-requests.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Filtro de Estado -->
                    <div>
                        <label for="statusFilter" class="block text-xs font-medium text-gray-700 mb-1.5 sm:mb-2">
                            <i class="fas fa-tag mr-1 sm:mr-2"></i>Estado
                        </label>
                        <select id="statusFilter" name="status" class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE" {{ request('status') == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                            <option value="ACEPTADA" {{ request('status') == 'ACEPTADA' ? 'selected' : '' }}>Aceptada</option>
                            <option value="EN_PROCESO" {{ request('status') == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
                            <option value="PAUSADA" {{ request('status') == 'PAUSADA' ? 'selected' : '' }}>Pausada</option>
                            <option value="RESUELTA" {{ request('status') == 'RESUELTA' ? 'selected' : '' }}>Resuelta</option>
                            <option value="CERRADA" {{ request('status') == 'CERRADA' ? 'selected' : '' }}>Cerrada</option>
                            <option value="CANCELADA" {{ request('status') == 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
                        </select>
                    </div>

                    <!-- Filtro de Criticidad -->
                    <div>
                        <label for="criticalityFilter" class="block text-xs font-medium text-gray-700 mb-1.5 sm:mb-2">
                            <i class="fas fa-flag mr-1 sm:mr-2"></i>Prioridad
                        </label>
                        <select id="criticalityFilter" name="criticality" class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas las prioridades</option>
                            <option value="BAJA" {{ request('criticality') == 'BAJA' ? 'selected' : '' }}>Baja</option>
                            <option value="MEDIA" {{ request('criticality') == 'MEDIA' ? 'selected' : '' }}>Media</option>
                            <option value="ALTA" {{ request('criticality') == 'ALTA' ? 'selected' : '' }}>Alta</option>
                            <option value="CRITICA" {{ request('criticality') == 'CRITICA' ? 'selected' : '' }}>Crítica</option>
                        </select>
                    </div>

                    <!-- Filtro de búsqueda por texto -->
                    <div class="col-span-1 lg:col-span-2">
                        <label for="searchFilter" class="block text-xs font-medium text-gray-700 mb-1.5 sm:mb-2">
                            <i class="fas fa-search mr-1 sm:mr-2"></i>Buscar solicitud
                        </label>
                        <input type="text" id="searchFilter" name="search" value="{{ request('search') }}" placeholder="Buscar por ticket, título o descripción..." class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </form>
        </div>

        <!-- Fila 3: Lista de Solicitudes -->
        <x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" />

        <!-- Fila 4: Paginación -->
        @if ($serviceRequests->hasPages())
            <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
        @endif

    </div>
@endsection

@section('scripts')
<script>
(function() {
    var resultsContainer = document.getElementById('resultsContainer');
    var timeout = null;

    function updateResults() {
        var searchFilter = document.getElementById('searchFilter');
        var statusFilter = document.getElementById('statusFilter');
        var criticalityFilter = document.getElementById('criticalityFilter');

        var params = new URLSearchParams();
        if (searchFilter.value.trim()) params.append('search', searchFilter.value.trim());
        if (statusFilter.value) params.append('status', statusFilter.value);
        if (criticalityFilter.value) params.append('criticality', criticalityFilter.value);

        searchFilter.style.borderColor = '#3b82f6';

        fetch('{{ route("service-requests.index") }}?' + params.toString(), {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            resultsContainer.innerHTML = html;
            var newSearchFilter = document.getElementById('searchFilter');
            if (newSearchFilter) {
                newSearchFilter.focus();
                newSearchFilter.style.borderColor = '#d1d5db';
            }
        });
    }

    function clearFilters() {
        fetch('{{ route("service-requests.index") }}', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            resultsContainer.innerHTML = html;
        });
    }

    // Usar delegación de eventos
    resultsContainer.addEventListener('input', function(e) {
        if (e.target.id === 'searchFilter') {
            clearTimeout(timeout);
            timeout = setTimeout(updateResults, 1200);
        }
    });

    resultsContainer.addEventListener('keydown', function(e) {
        if (e.target.id === 'searchFilter' && e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            clearTimeout(timeout);
            updateResults();
        }
    });

    resultsContainer.addEventListener('change', function(e) {
        if (e.target.id === 'statusFilter' || e.target.id === 'criticalityFilter') {
            updateResults();
        }
    });

    resultsContainer.addEventListener('click', function(e) {
        if (e.target.id === 'clearFiltersBtn' || e.target.closest('#clearFiltersBtn')) {
            e.preventDefault();
            clearFilters();
        }
    });
})();
</script>
@endsection

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
