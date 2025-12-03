@props(['serviceRequests'])

<div class="bg-white rounded-lg shadow-sm border border-gray-200" role="region" aria-labelledby="requests-table-title">
    <!-- Header Compacto -->
    <div class="bg-gray-50 px-3 sm:px-4 py-2.5 sm:py-3 border-b border-gray-200">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h3 id="requests-table-title" class="text-sm sm:text-base font-semibold text-gray-800 flex items-center">
                <i class="fas fa-tasks text-blue-500 mr-1.5 sm:mr-2 text-xs sm:text-sm"></i>
                <span class="hidden sm:inline">Solicitudes de Servicio</span>
                <span class="sm:hidden">Solicitudes</span>
                <span class="ml-1.5 sm:ml-2 text-xs sm:text-sm font-medium text-gray-500 bg-gray-200 px-1.5 sm:px-2 py-0.5 rounded-full">
                    {{ $serviceRequests->total() }}
                </span>
            </h3>

            <!-- Estado Resumido -->
            <div class="flex items-center gap-2 sm:gap-3 text-xs">
                <span class="flex items-center text-yellow-600">
                    <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full mr-1"></span>
                    {{ $pendingCount ?? 0 }}
                </span>
                <span class="flex items-center text-red-600">
                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1"></span>
                    {{ $criticalCount ?? 0 }}
                </span>
                <span class="flex items-center text-green-600">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></span>
                    {{ $resolvedCount ?? 0 }}
                </span>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros Integrada -->
    <div class="px-3 sm:px-4 py-2 bg-white border-b border-gray-200 relative" role="toolbar" aria-label="Filtros de solicitudes">
        <form id="inlineFiltersForm" class="grid grid-cols-2 md:grid-cols-6 gap-2" action="{{ route('service-requests.index') }}" method="GET" onsubmit="return false;">
            @if(request('open'))
                <input type="hidden" id="openFilter" name="open" value="1">
            @endif
            <div>
                <label for="searchFilter" class="sr-only">Buscar</label>
                <input id="searchFilter" name="search" value="{{ request('search') }}" type="text" placeholder="Buscar..." class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div>
                <label for="statusFilter" class="sr-only">Estado</label>
                <select id="statusFilter" name="status" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Estado</option>
                    @foreach(['PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA','RESUELTA','CERRADA','CANCELADA'] as $st)
                        <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ ucfirst(strtolower(str_replace('_',' ', $st))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="criticalityFilter" class="sr-only">Prioridad</label>
                <select id="criticalityFilter" name="criticality" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Prioridad</option>
                    @foreach(['BAJA','MEDIA','ALTA','CRITICA'] as $crit)
                        <option value="{{ $crit }}" {{ request('criticality')===$crit?'selected':'' }}>{{ ucfirst(strtolower($crit)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative">
                <label for="requesterFilter" class="sr-only">Solicitante</label>
                <input id="requesterFilter" name="requester" value="{{ request('requester') }}" type="text" placeholder="Solicitante" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off" aria-autocomplete="list" aria-expanded="false" aria-owns="requesterSuggestions" />
                <ul id="requesterSuggestions" class="absolute z-30 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded shadow hidden max-h-40 overflow-auto text-xs" role="listbox"></ul>
            </div>
            <div>
                <label for="startDateFilter" class="sr-only">Desde</label>
                <input id="startDateFilter" name="start_date" value="{{ request('start_date') }}" type="date" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label for="endDateFilter" class="sr-only">Hasta</label>
                    <input id="endDateFilter" name="end_date" value="{{ request('end_date') }}" type="date" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <button id="clearFiltersBtn" type="button" class="px-2 py-1.5 border border-gray-300 rounded text-xs bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500" aria-label="Limpiar filtros">
                    <i class="fas fa-times text-gray-500"></i>
                </button>
            </div>
        </form>
        <div id="filtersActiveBadge" class="absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full shadow hidden" aria-hidden="true"></div>
        <div id="loadingSpinner" class="absolute inset-0 bg-white/70 backdrop-blur-sm items-center justify-center rounded hidden">
            <div class="flex flex-col items-center gap-1">
                <div class="animate-spin h-5 w-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
                <span class="text-[10px] text-blue-700 font-medium">Actualizando...</span>
            </div>
        </div>
    </div>

    <!-- Contenido Compacto -->
    <div class="p-3 sm:p-4" id="tableContainer">
        @if ($serviceRequests->count() > 0)
            <div class="overflow-x-auto -mx-3 sm:mx-0">
                <!-- Tabla Compacta -->
                <table class="min-w-full text-xs sm:text-sm" aria-describedby="table-instructions">
                    <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
                        <tr>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Ticket</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium w-1/5 hidden md:table-cell">Solicitud</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium w-1/5 hidden lg:table-cell">Servicio</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Prioridad</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Estado</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium hidden sm:table-cell">Solicitante</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium hidden xl:table-cell">Fecha</th>
                            <th class="px-2 sm:px-3 py-1.5 sm:py-2 text-left font-medium">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach ($serviceRequests as $request)
                            <x-service-requests.index.content.table-row :request="$request" />
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Estado Vacío Compacto -->
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-3xl mb-3"></i>
                <p class="text-gray-500 text-sm">No se encontraron solicitudes</p>
            </div>
        @endif
    </div>

    <!-- Footer Compacto -->
    @if ($serviceRequests->hasPages())
        <div class="bg-gray-50 px-4 py-2 border-t border-gray-200">
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>Página {{ $serviceRequests->currentPage() }} de {{ $serviceRequests->lastPage() }}</span>
                <span>Actualizado: {{ now()->format('H:i') }}</span>
            </div>
        </div>
    @endif
</div>

<style>
    /* Mejoras de densidad */
    .min-w-full th,
    .min-w-full td {
        padding: 0.5rem 0.75rem;
    }

    /* Estados de enfoque para accesibilidad */
    tr:focus {
        outline: 2px solid #3b82f6;
        background-color: #eff6ff;
    }

    /* Hover sutil */
    tr:hover {
        background-color: #f9fafb;
    }
    /* Dropdown sugerencias */
    #requesterSuggestions li { padding:0.4rem 0.6rem; }
    #requesterSuggestions li:hover, #requesterSuggestions li[aria-selected='true'] { background:#eef2ff; }
</style>
