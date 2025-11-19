<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 lg:gap-6">
    <x-service-requests.index.stats-cards.create-action />
    <x-service-requests.index.stats-cards.critical-stats :count="$criticalCount ?? 0" />
    <x-service-requests.index.stats-cards.pending-stats :count="$pendingCount ?? 0" />
    <x-service-requests.index.stats-cards.closed-stats :count="$closedCount ?? 0" />
    <x-service-requests.index.stats-cards.total-stats :count="$serviceRequests->total()" />
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg border border-gray-200 p-3 sm:p-4 md:p-5 mb-4 sm:mb-6">
    <div class="flex items-center justify-between mb-3 sm:mb-4">
        <h3 class="text-sm sm:text-base font-semibold text-gray-800 flex items-center">
            <i class="fas fa-filter text-blue-500 mr-2 text-xs sm:text-sm"></i>
            Filtros
        </h3>
        <button type="button" id="clearFiltersBtn" class="text-xs sm:text-sm text-gray-600 hover:text-red-600 font-medium transition-colors">
            <i class="fas fa-times-circle mr-1"></i>Limpiar filtros
        </button>
    </div>

    <form id="filtersForm" method="GET" action="{{ route('service-requests.index') }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
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

            <div class="col-span-1 lg:col-span-2">
                <label for="searchFilter" class="block text-xs font-medium text-gray-700 mb-1.5 sm:mb-2">
                    <i class="fas fa-search mr-1 sm:mr-2"></i>Buscar solicitud
                </label>
                <input type="text" id="searchFilter" name="search" value="{{ request('search') }}" placeholder="Buscar por ticket, título o descripción..." class="w-full px-2.5 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </form>
</div>

<!-- Tabla -->
<x-service-requests.index.content.requests-table :serviceRequests="$serviceRequests" />

<!-- Paginación -->
@if ($serviceRequests->hasPages())
    <x-service-requests.index.content.pagination :serviceRequests="$serviceRequests" />
@endif
