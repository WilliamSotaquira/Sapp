@props(['serviceRequests', 'services' => null])

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

            <!-- Toggle Vista Tabla/Tarjetas -->
            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
                <button id="viewToggleTable" onclick="toggleView('table')" class="px-2 py-1 text-xs rounded transition-colors bg-white shadow-sm text-gray-900">
                    <i class="fas fa-list mr-1"></i>
                    <span class="hidden sm:inline">Tabla</span>
                </button>
                <button id="viewToggleCards" onclick="toggleView('cards')" class="px-2 py-1 text-xs rounded transition-colors text-gray-600">
                    <i class="fas fa-th-large mr-1"></i>
                    <span class="hidden sm:inline">Tarjetas</span>
                </button>
            </div>

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

    <!-- Nueva Barra de Búsqueda y Filtros Rápidos -->
    <div class="px-3 sm:px-4 py-3 bg-white border-b border-gray-200">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Búsqueda Principal -->
            <div class="flex-1 relative">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input id="searchFilter" 
                           name="search" 
                           value="{{ request('search') }}" 
                           type="text" 
                           placeholder="Buscar por ticket, título o descripción..." 
                           class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                           autocomplete="off">
                    <button type="button" 
                            id="clearSearchBtn"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                <!-- Historial de Búsqueda -->
                <div id="searchHistory" class="absolute z-40 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-auto">
                    <div class="p-2 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700">Búsquedas Recientes</span>
                        <button type="button" id="clearHistoryBtn" class="text-xs text-blue-600 hover:text-blue-800">Limpiar</button>
                    </div>
                    <ul id="searchHistoryList" class="divide-y divide-gray-100"></ul>
                </div>
            </div>

            <!-- Botón Filtros Avanzados -->
            <button type="button" 
                    id="toggleFiltersSidebar"
                    class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm font-medium shadow-sm">
                <i class="fas fa-filter"></i>
                <span>Filtros</span>
                <span id="activeFiltersCount" class="hidden bg-white text-blue-600 px-2 py-0.5 rounded-full text-xs font-bold">0</span>
            </button>
        </div>

        <!-- Filtros Rápidos -->
        <div class="flex gap-2 mt-3 flex-wrap">
            <button type="button" onclick="applyQuickFilter('criticality', 'CRITICA')" 
                    class="quick-filter px-3 py-1.5 text-xs font-medium rounded-full border border-red-300 text-red-700 hover:bg-red-50 transition-colors">
                <i class="fas fa-exclamation-circle mr-1"></i>Críticas
            </button>
            <button type="button" onclick="applyQuickFilter('status', 'PENDIENTE')" 
                    class="quick-filter px-3 py-1.5 text-xs font-medium rounded-full border border-yellow-300 text-yellow-700 hover:bg-yellow-50 transition-colors">
                <i class="fas fa-clock mr-1"></i>Pendientes
            </button>
            <button type="button" onclick="applyQuickFilter('status', 'EN_PROCESO')" 
                    class="quick-filter px-3 py-1.5 text-xs font-medium rounded-full border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors">
                <i class="fas fa-spinner mr-1"></i>En Proceso
            </button>
            <button type="button" onclick="applyQuickFilter('open', '1')" 
                    class="quick-filter px-3 py-1.5 text-xs font-medium rounded-full border border-green-300 text-green-700 hover:bg-green-50 transition-colors">
                <i class="fas fa-folder-open mr-1"></i>Abiertas
            </button>
            <button type="button" id="showPresetsBtn"
                    class="px-3 py-1.5 text-xs font-medium rounded-full border border-purple-300 text-purple-700 hover:bg-purple-50 transition-colors">
                <i class="fas fa-star mr-1"></i>Presets
            </button>
        </div>
    </div>

    <!-- Sidebar de Filtros Avanzados -->
    <div id="filtersSidebar" class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
        <div class="flex flex-col h-full">
            <!-- Header del Sidebar -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-sliders-h mr-2"></i>
                        Filtros Avanzados
                    </h3>
                    <button type="button" id="closeFiltersSidebar" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-blue-100 text-xs mt-1">Personaliza tu búsqueda</p>
            </div>

            <!-- Contenido del Sidebar -->
            <div class="flex-1 px-6 py-4 space-y-6">
                <form id="advancedFiltersForm" class="space-y-4">
                    @if(request('open'))
                        <input type="hidden" id="openFilter" name="open" value="1">
                    @endif

                    <!-- Estado -->
                    <div>
                        <label for="statusFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select id="statusFilterAdv" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos los estados</option>
                            @foreach(['PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA','RESUELTA','CERRADA','CANCELADA'] as $st)
                                <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ ucfirst(strtolower(str_replace('_',' ', $st))) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Prioridad -->
                    <div>
                        <label for="criticalityFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                        <select id="criticalityFilterAdv" name="criticality" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas las prioridades</option>
                            @foreach(['BAJA','MEDIA','ALTA','CRITICA'] as $crit)
                                <option value="{{ $crit }}" {{ request('criticality')===$crit?'selected':'' }}>{{ ucfirst(strtolower($crit)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Servicio -->
                    <div>
                        <label for="serviceFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                        <select id="serviceFilterAdv" name="service_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos los servicios</option>
                            @isset($services)
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}" {{ (string) request('service_id') === (string) $service->id ? 'selected' : '' }}>
                                        {{ $service->family->name ?? 'Sin familia' }} - {{ $service->name }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Solicitante -->
                    <div>
                        <label for="requesterFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Solicitante</label>
                        <input id="requesterFilterAdv" name="requester" value="{{ request('requester') }}" type="text" placeholder="Nombre o email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Rango de Fechas</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="startDateFilterAdv" class="block text-xs text-gray-600 mb-1">Desde</label>
                                <input id="startDateFilterAdv" name="start_date" value="{{ request('start_date') }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="endDateFilterAdv" class="block text-xs text-gray-600 mb-1">Hasta</label>
                                <input id="endDateFilterAdv" name="end_date" value="{{ request('end_date') }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Presets Guardados -->
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700">Presets Guardados</h4>
                        <button type="button" onclick="savePreset()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-plus-circle mr-1"></i>Guardar
                        </button>
                    </div>
                    <div id="presetsContainer" class="space-y-2">
                        <p class="text-sm text-gray-500 text-center py-4">No hay presets guardados</p>
                    </div>
                </div>
            </div>

            <!-- Footer del Sidebar con Acciones -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex gap-3">
                <button type="button" onclick="clearAllFilters()" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Limpiar
                </button>
                <button type="button" onclick="applyFilters()" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fas fa-check mr-2"></i>Aplicar
                </button>
            </div>
        </div>
    </div>

    <!-- Overlay para cerrar sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="document.getElementById('filtersSidebar').classList.add('translate-x-full'); this.classList.add('hidden');"></div>

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

            <!-- Vista de Tarjetas (Inicialmente oculta) -->
            <div id="cardsView" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($serviceRequests as $request)
                    @php
                        $name = $request->requester->name ?? 'N/A';
                        $initials = collect(explode(' ', $name))->map(fn($word) => strtoupper(substr($word, 0, 1)))->take(2)->join('');
                        $colors = ['from-purple-500 to-pink-500', 'from-blue-500 to-cyan-500', 'from-green-500 to-emerald-500', 'from-orange-500 to-red-500', 'from-indigo-500 to-purple-500'];
                        $colorIndex = ord(substr($name, 0, 1)) % count($colors);
                        
                        $statusColors = [
                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'ACEPTADA' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'EN_PROCESO' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            'PAUSADA' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'RESUELTA' => 'bg-green-100 text-green-800 border-green-200',
                            'CERRADA' => 'bg-gray-100 text-gray-800 border-gray-200',
                            'CANCELADA' => 'bg-red-100 text-red-800 border-red-200'
                        ];
                        
                        $criticalityColors = [
                            'CRITICA' => 'bg-red-600 text-white',
                            'ALTA' => 'bg-orange-500 text-white',
                            'MEDIA' => 'bg-yellow-500 text-white',
                            'BAJA' => 'bg-green-500 text-white'
                        ];
                    @endphp
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow overflow-hidden">
                        <!-- Header de la tarjeta -->
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex items-start justify-between mb-2">
                                <a href="{{ route('service-requests.show', $request) }}" 
                                   class="font-mono text-blue-600 hover:text-blue-800 hover:underline font-bold text-sm">
                                    {{ $request->ticket_number }}
                                </a>
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $criticalityColors[$request->criticality_level] ?? 'bg-gray-500 text-white' }}">
                                    {{ $request->criticality_level }}
                                </span>
                            </div>
                            <h4 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-2">
                                {{ $request->title }}
                            </h4>
                            <p class="text-xs text-gray-600 line-clamp-2">
                                {{ $request->description }}
                            </p>
                        </div>
                        
                        <!-- Body de la tarjeta -->
                        <div class="p-4 space-y-3">
                            <!-- Servicio -->
                            <div class="flex items-start gap-2">
                                <i class="fas fa-cog text-gray-400 text-xs mt-0.5"></i>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-900 truncate">
                                        {{ $request->subService->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ $request->subService->service->family->name ?? '' }}
                                    </div>
                                </div>
                            </div>

                            
                            <!-- Solicitante -->
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gradient-to-br {{ $colors[$colorIndex] }} rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm flex-shrink-0">
                                    {{ $initials }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-900 truncate">{{ $name }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->created_at->locale('es')->diffForHumans() }}</div>
                                </div>
                            </div>
                            
                            <!-- Estado -->
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <span class="px-3 py-1 text-xs font-semibold rounded border {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    {{ ucfirst(strtolower(str_replace('_', ' ', $request->status))) }}
                                </span>
                                <div class="flex gap-2">
                                    <a href="{{ route('service-requests.show', $request) }}" 
                                       class="text-blue-600 hover:text-blue-800 transition-colors" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('service-requests.edit', $request) }}" 
                                       class="text-yellow-600 hover:text-yellow-800 transition-colors" 
                                       title="Editar">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
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

<script>
function toggleView(view) {
    const tableView = document.querySelector('.overflow-x-auto');
    const cardsView = document.getElementById('cardsView');
    const tableBtn = document.getElementById('viewToggleTable');
    const cardsBtn = document.getElementById('viewToggleCards');
    
    if (view === 'table') {
        tableView.classList.remove('hidden');
        cardsView.classList.add('hidden');
        tableBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
        tableBtn.classList.remove('text-gray-600');
        cardsBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
        cardsBtn.classList.add('text-gray-600');
    } else {
        tableView.classList.add('hidden');
        cardsView.classList.remove('hidden');
        cardsBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
        cardsBtn.classList.remove('text-gray-600');
        tableBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
        tableBtn.classList.add('text-gray-600');
    }
    
    // Guardar preferencia en localStorage
    try {
        localStorage.setItem('sr_view_preference', view);
    } catch(e) {}
}

// Restaurar preferencia al cargar
document.addEventListener('DOMContentLoaded', function() {
    try {
        const savedView = localStorage.getItem('sr_view_preference') || 'table';
        if (savedView === 'cards') {
            toggleView('cards');
        }
    } catch(e) {}
});

// === SISTEMA DE FILTROS AVANZADOS ===
const STORAGE_KEYS = {
    searchHistory: 'sr_search_history',
    filterPresets: 'sr_filter_presets'
};

// === Sidebar Toggle ===
document.getElementById('toggleFiltersSidebar')?.addEventListener('click', function() {
    document.getElementById('filtersSidebar').classList.remove('translate-x-full');
    document.getElementById('sidebarOverlay').classList.remove('hidden');
});

document.getElementById('closeFiltersSidebar')?.addEventListener('click', function() {
    document.getElementById('filtersSidebar').classList.add('translate-x-full');
    document.getElementById('sidebarOverlay').classList.add('hidden');
});

// === Historial de Búsqueda ===
function saveSearchHistory(term) {
    if (!term || term.length < 3) return;
    try {
        let history = JSON.parse(localStorage.getItem(STORAGE_KEYS.searchHistory) || '[]');
        history = history.filter(h => h !== term);
        history.unshift(term);
        history = history.slice(0, 10);
        localStorage.setItem(STORAGE_KEYS.searchHistory, JSON.stringify(history));
    } catch(e) {}
}

function loadSearchHistory() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEYS.searchHistory) || '[]');
    } catch(e) {
        return [];
    }
}

function renderSearchHistory() {
    const history = loadSearchHistory();
    const list = document.getElementById('searchHistoryList');
    const container = document.getElementById('searchHistory');
    
    if (!list || !container) return;
    
    if (history.length === 0) {
        container.classList.add('hidden');
        return;
    }
    
    list.innerHTML = history.map(term => 
        `<li class="px-4 py-2 hover:bg-gray-50 cursor-pointer text-sm text-gray-700 flex items-center gap-2" 
            onclick="applySearchFromHistory('${term.replace(/'/g, "\\'")}')">
            <i class="fas fa-history text-gray-400 text-xs"></i>
            ${term}
        </li>`
    ).join('');
}

function applySearchFromHistory(term) {
    document.getElementById('searchFilter').value = term;
    document.getElementById('searchHistory').classList.add('hidden');
    const params = new URLSearchParams(window.location.search);
    params.set('search', term);
    window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
}

// === Filtros Rápidos ===
function applyQuickFilter(field, value) {
    const params = new URLSearchParams();
    params.set(field, value);
    window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
}

// === Sistema de Presets ===
function savePreset() {
    const name = prompt('Nombre del preset:');
    if (!name) return;
    
    const filters = gatherFilters();
    try {
        let presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        presets[name] = filters;
        localStorage.setItem(STORAGE_KEYS.filterPresets, JSON.stringify(presets));
        renderPresets();
        showToast('Preset guardado exitosamente');
    } catch(e) {
        showToast('Error al guardar preset', 'error');
    }
}

function loadPreset(name) {
    try {
        const presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        const filters = presets[name];
        if (!filters) return;
        
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.set(key, filters[key]);
        });
        
        window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
    } catch(e) {}
}

function deletePreset(name) {
    if (!confirm(`¿Eliminar preset "${name}"?`)) return;
    
    try {
        let presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        delete presets[name];
        localStorage.setItem(STORAGE_KEYS.filterPresets, JSON.stringify(presets));
        renderPresets();
        showToast('Preset eliminado');
    } catch(e) {}
}

function renderPresets() {
    const container = document.getElementById('presetsContainer');
    if (!container) return;
    
    try {
        const presets = JSON.parse(localStorage.getItem(STORAGE_KEYS.filterPresets) || '{}');
        const names = Object.keys(presets);
        
        if (names.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No hay presets guardados</p>';
            return;
        }
        
        container.innerHTML = names.map(name => 
            `<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <button type="button" onclick="loadPreset('${name.replace(/'/g, "\\'")}')"
                        class="flex-1 text-left text-sm font-medium text-gray-700">
                    <i class="fas fa-star text-purple-500 mr-2"></i>${name}
                </button>
                <button type="button" onclick="deletePreset('${name.replace(/'/g, "\\'")}')"
                        class="text-red-500 hover:text-red-700 ml-2">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>`
        ).join('');
    } catch(e) {}
}

// === Aplicar Filtros ===
function gatherFilters() {
    return {
        search: document.getElementById('searchFilter')?.value || '',
        status: document.getElementById('statusFilterAdv')?.value || '',
        criticality: document.getElementById('criticalityFilterAdv')?.value || '',
        service_id: document.getElementById('serviceFilterAdv')?.value || '',
        requester: document.getElementById('requesterFilterAdv')?.value || '',
        start_date: document.getElementById('startDateFilterAdv')?.value || '',
        end_date: document.getElementById('endDateFilterAdv')?.value || ''
    };
}

function applyFilters() {
    const filters = gatherFilters();
    
    if (filters.search) {
        saveSearchHistory(filters.search);
    }
    
    const params = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) params.append(key, filters[key]);
    });
    
    window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
}

function clearAllFilters() {
    // Limpieza del estado persistido por el filtro AJAX (resources/views/service-requests/index.blade.php)
    // para que al recargar no se re-apliquen filtros antiguos desde localStorage.
    try { localStorage.removeItem('sr_filters_v1'); } catch(e) {}
    window.location.href = '{{ route("service-requests.index") }}';
}

// === UI Helpers ===
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white z-50 transition-opacity ${
        type === 'error' ? 'bg-red-500' : 'bg-green-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function updateActiveFiltersCount() {
    const params = new URLSearchParams(window.location.search);
    const count = Array.from(params.keys()).filter(k => k !== 'page').length;
    const badge = document.getElementById('activeFiltersCount');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

// === Event Listeners ===
const searchInput = document.getElementById('searchFilter');
const clearSearchBtn = document.getElementById('clearSearchBtn');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        if (clearSearchBtn) {
            clearSearchBtn.classList.toggle('hidden', !this.value);
        }
    });
    
    searchInput.addEventListener('focus', function() {
        renderSearchHistory();
        if (loadSearchHistory().length > 0) {
            const hist = document.getElementById('searchHistory');
            if (hist) hist.classList.remove('hidden');
        }
    });
    
    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            const hist = document.getElementById('searchHistory');
            if (hist) hist.classList.add('hidden');
        }, 200);
    });
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const params = new URLSearchParams(window.location.search);
            if (this.value) {
                params.set('search', this.value);
                saveSearchHistory(this.value);
            } else {
                params.delete('search');
            }
            window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
        }
    });
}

if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', function() {
        if (searchInput) {
            searchInput.value = '';
            this.classList.add('hidden');
        }
        // Evitar que el filtro AJAX restaure la búsqueda anterior al recargar.
        try {
            const raw = localStorage.getItem('sr_filters_v1');
            if (raw) {
                const state = JSON.parse(raw) || {};
                state.search = '';
                localStorage.setItem('sr_filters_v1', JSON.stringify(state));
            }
        } catch(e) {}
        const params = new URLSearchParams(window.location.search);
        params.delete('search');
        window.location.href = '{{ route("service-requests.index") }}?' + (params.toString() || '');
    });
}

document.getElementById('clearHistoryBtn')?.addEventListener('click', function() {
    localStorage.removeItem(STORAGE_KEYS.searchHistory);
    const hist = document.getElementById('searchHistory');
    if (hist) hist.classList.add('hidden');
});

document.getElementById('showPresetsBtn')?.addEventListener('click', function() {
    renderPresets();
    document.getElementById('filtersSidebar').classList.remove('translate-x-full');
    document.getElementById('sidebarOverlay').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('presetsContainer')?.scrollIntoView({ behavior: 'smooth' });
    }, 300);
});

// Inicializar
updateActiveFiltersCount();
if (clearSearchBtn && searchInput && searchInput.value) {
    clearSearchBtn.classList.remove('hidden');
}
renderPresets();
</script>
