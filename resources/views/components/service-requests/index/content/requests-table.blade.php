@props(['serviceRequests', 'services' => null, 'savedFilters' => collect()])

@php
    $activeFilters = [];
    $search = request('q', request('search'));
    $status = request('status');
    $criticality = request('criticality');
    $dueStatus = request('due_status');
    $requester = request('requester');
    $startDate = request('start_date');
    $endDate = request('end_date');
    $open = request('open');
    $excludeClosed = request('exclude_closed');
    $inCourse = request('in_course');
    $inProcess = request('in_process');
    $serviceId = request('service_id');
    $sortBy = request('sort_by', 'recent');
    $baseParams = request()->except(['page']);

    if ($search) $activeFilters[] = ['label' => 'Búsqueda: ' . \Illuminate\Support\Str::limit($search, 30), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['search' => true]))];
    if ($status) $activeFilters[] = ['label' => 'Estado: ' . ucfirst(strtolower(str_replace('_',' ', $status))), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['status' => true]))];
    if ($criticality) $activeFilters[] = ['label' => 'Prioridad: ' . ucfirst(strtolower($criticality)), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['criticality' => true]))];
    if ($dueStatus) {
        $dueStatusLabels = [
            'with_due' => 'Con vencimiento',
            'without_due' => 'Sin vencimiento',
            'overdue' => 'Vencidas',
            'due_soon' => 'Por vencer',
        ];
        $activeFilters[] = ['label' => 'Vencimiento: ' . ($dueStatusLabels[$dueStatus] ?? $dueStatus), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['due_status' => true]))];
    }
    if ($requester) $activeFilters[] = ['label' => 'Solicitante: ' . \Illuminate\Support\Str::limit($requester, 24), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['requester' => true]))];
    if ($serviceId && $services) {
        $serviceName = optional($services->firstWhere('id', (int) $serviceId))->name;
        if ($serviceName) $activeFilters[] = ['label' => 'Servicio: ' . \Illuminate\Support\Str::limit($serviceName, 28), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['service_id' => true]))];
    }
    if ($startDate || $endDate) {
        $rangeLabel = trim(($startDate ?: '...') . ' a ' . ($endDate ?: '...'));
        $activeFilters[] = ['label' => 'Fecha solicitud: ' . $rangeLabel, 'remove' => route('service-requests.index', array_diff_key($baseParams, ['start_date' => true, 'end_date' => true]))];
    }
    if ($open) $activeFilters[] = ['label' => 'Solo abiertas', 'remove' => route('service-requests.index', array_diff_key($baseParams, ['open' => true]))];
    if ($excludeClosed) $activeFilters[] = ['label' => 'Sin cerradas', 'remove' => route('service-requests.index', array_diff_key($baseParams, ['exclude_closed' => true]))];
    if ($inCourse) $activeFilters[] = ['label' => 'En espera', 'remove' => route('service-requests.index', array_diff_key($baseParams, ['in_course' => true]))];
    if ($inProcess) $activeFilters[] = ['label' => 'En proceso', 'remove' => route('service-requests.index', array_diff_key($baseParams, ['in_process' => true]))];
    if ($sortBy && $sortBy !== 'recent') {
        $sortLabels = [
            'oldest' => 'Fecha de solicitud más antigua',
            'priority_high' => 'Prioridad alta a baja',
            'priority_low' => 'Prioridad baja a alta',
            'status_az' => 'Estado A-Z',
            'status_za' => 'Estado Z-A',
            'due_date' => 'Vencimiento más cercano',
        ];
        $activeFilters[] = ['label' => 'Orden: ' . ($sortLabels[$sortBy] ?? $sortBy), 'remove' => route('service-requests.index', array_diff_key($baseParams, ['sort_by' => true]))];
    }

    $quickFilters = [
        [
            'field' => 'criticality',
            'value' => 'CRITICA',
            'label' => 'Críticas',
            'icon' => 'fa-exclamation-circle',
            'iconClass' => 'text-red-500',
            'active' => $criticality === 'CRITICA',
        ],
        [
            'field' => 'in_course',
            'value' => '1',
            'label' => 'En espera',
            'icon' => 'fa-hourglass-half',
            'iconClass' => 'text-amber-500',
            'active' => (string) $inCourse === '1',
        ],
        [
            'field' => 'status',
            'value' => 'PENDIENTE',
            'label' => 'Pendientes',
            'icon' => 'fa-clock',
            'iconClass' => 'text-yellow-600',
            'active' => $status === 'PENDIENTE',
        ],
        [
            'field' => 'status',
            'value' => 'EN_PROCESO',
            'label' => 'En proceso',
            'icon' => 'fa-spinner',
            'iconClass' => 'text-blue-500',
            'active' => $status === 'EN_PROCESO',
        ],
        [
            'field' => 'due_status',
            'value' => 'overdue',
            'label' => 'Vencidas',
            'icon' => 'fa-calendar-times',
            'iconClass' => 'text-red-500',
            'active' => $dueStatus === 'overdue',
        ],
        [
            'field' => 'due_status',
            'value' => 'due_soon',
            'label' => 'Por vencer',
            'icon' => 'fa-calendar-day',
            'iconClass' => 'text-amber-500',
            'active' => $dueStatus === 'due_soon',
        ],
        [
            'field' => 'open',
            'value' => '1',
            'label' => 'Abiertas',
            'icon' => 'fa-folder-open',
            'iconClass' => 'text-emerald-600',
            'active' => (string) $open === '1',
        ],
        [
            'field' => 'status',
            'value' => 'PAUSADA',
            'label' => 'Pausadas',
            'icon' => 'fa-pause-circle',
            'iconClass' => 'text-orange-500',
            'active' => $status === 'PAUSADA',
        ],
        [
            'field' => 'status',
            'value' => 'RECHAZADA',
            'label' => 'Rechazadas',
            'icon' => 'fa-ban',
            'iconClass' => 'text-rose-500',
            'active' => $status === 'RECHAZADA',
        ],
        [
            'field' => 'status',
            'value' => 'CERRADA',
            'label' => 'Cerradas',
            'icon' => 'fa-lock',
            'iconClass' => 'text-slate-500',
            'active' => $status === 'CERRADA',
        ],
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" role="region" aria-labelledby="requests-table-title">
    <!-- Header Compacto -->
    <div class="bg-slate-50 px-4 sm:px-5 py-3 border-b border-gray-200">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <div class="min-w-0">
                <h3 id="requests-table-title" class="text-base font-semibold text-slate-900 flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <i class="fas fa-tasks text-sm"></i>
                    </span>
                    <span>Solicitudes de Servicio</span>
                    <span class="text-xs font-semibold text-slate-600 bg-white border border-slate-200 px-2 py-1 rounded-full">
                        {{ $serviceRequests->total() }}
                    </span>
                </h3>
                <p class="mt-1 text-xs text-slate-500">Listado operativo con seguimiento de SLA y vencimientos de solicitud.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Pendientes: {{ $pendingCount ?? 0 }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Críticas: {{ $criticalCount ?? 0 }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Resueltas: {{ $resolvedCount ?? 0 }}
                </span>
                <div class="flex items-center gap-1 bg-white border border-slate-200 p-1 rounded-lg">
                    <button id="viewToggleTable" onclick="toggleView('table')" class="px-2.5 py-1.5 text-xs rounded-md transition-colors bg-slate-900 text-white shadow-sm">
                        <i class="fas fa-list mr-1"></i>
                        <span>Tabla</span>
                    </button>
                    <button id="viewToggleCards" onclick="toggleView('cards')" class="px-2.5 py-1.5 text-xs rounded-md transition-colors text-slate-600 hover:text-slate-900">
                        <i class="fas fa-th-large mr-1"></i>
                        <span>Tarjetas</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Nueva Barra de Búsqueda y Filtros Rápidos -->
    <div class="px-4 sm:px-5 py-4 bg-white border-b border-gray-200">
        <div class="flex flex-col lg:flex-row gap-3">
            <!-- Búsqueda Principal -->
            <div class="flex-1 relative">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input id="searchFilter" 
                           name="search" 
                           value="{{ request('q', request('search')) }}" 
                           type="text" 
                           placeholder="Búsqueda global: ticket, título, solicitante, servicio, familia o contrato..." 
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
                    class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center justify-center gap-2 text-sm font-medium shadow-sm">
                <i class="fas fa-filter"></i>
                <span>Filtros</span>
                <span id="activeFiltersCount" class="hidden bg-white text-blue-600 px-2 py-0.5 rounded-full text-xs font-bold">0</span>
            </button>
        </div>

        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50/70 p-1.5">
            <div class="sr-quick-filters flex items-center gap-1.5">
                @foreach($quickFilters as $filter)
                    <button type="button"
                            onclick="applyQuickFilter('{{ $filter['field'] }}', '{{ $filter['value'] }}')"
                            class="quick-filter inline-flex min-w-0 flex-1 items-center justify-center gap-1 rounded-md border px-1.5 py-1 text-[11px] font-semibold transition-colors {{ $filter['active'] ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-100' }}"
                            title="Filtrar por {{ $filter['label'] }}">
                        <i class="fas {{ $filter['icon'] }} {{ $filter['active'] ? 'text-white' : $filter['iconClass'] }} text-[11px]"></i>
                        <span class="truncate">{{ $filter['label'] }}</span>
                    </button>
                @endforeach
                <button type="button" id="showPresetsBtn"
                        class="inline-flex min-w-0 flex-1 items-center justify-center gap-1 rounded-md border border-slate-200 bg-white px-1.5 py-1 text-[11px] font-semibold text-slate-700 transition-colors hover:border-purple-200 hover:bg-purple-50 hover:text-purple-700"
                        title="Abrir presets de filtros">
                    <i class="fas fa-star text-purple-500 text-[11px]"></i>
                    <span class="truncate">Presets</span>
                </button>
                <button type="button" onclick="applyQuickFilter('all', '1')"
                        class="quick-filter inline-flex min-w-0 flex-1 items-center justify-center gap-1 rounded-md border border-slate-200 bg-white px-1.5 py-1 text-[11px] font-semibold text-slate-700 transition-colors hover:border-slate-300 hover:bg-slate-100"
                        title="Ver todas las solicitudes">
                    <i class="fas fa-layer-group text-slate-500 text-[11px]"></i>
                    <span class="truncate">Total</span>
                </button>
            </div>
        </div>

        @if (count($activeFilters) > 0)
            <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-blue-900">Filtros activos</span>
                @foreach ($activeFilters as $filter)
                    <span class="inline-flex items-center gap-1 bg-white text-blue-700 border border-blue-200 px-2 py-1 rounded-full text-[11px] font-medium">
                        <i class="fas fa-filter text-[10px]"></i>
                        {{ $filter['label'] }}
                        <a href="{{ $filter['remove'] }}" class="ml-1 text-blue-600 hover:text-blue-800" aria-label="Quitar filtro">
                            <i class="fas fa-times text-[10px]"></i>
                        </a>
                    </span>
                @endforeach
                <a href="{{ route('service-requests.index') }}"
                   class="text-xs font-semibold text-blue-700 hover:text-blue-900 ml-1">Limpiar filtros</a>
            </div>
        @endif
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
                    @if(request('in_course'))
                        <input type="hidden" id="inCourseFilter" name="in_course" value="1">
                    @endif
                    @if(request('in_process'))
                        <input type="hidden" id="inProcessFilter" name="in_process" value="1">
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

                    <!-- Vencimiento -->
                    <div>
                        <label for="dueStatusFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Vencimiento</label>
                        <select id="dueStatusFilterAdv" name="due_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos</option>
                            <option value="with_due" {{ request('due_status') === 'with_due' ? 'selected' : '' }}>Con vencimiento</option>
                            <option value="overdue" {{ request('due_status') === 'overdue' ? 'selected' : '' }}>Vencidas</option>
                            <option value="due_soon" {{ request('due_status') === 'due_soon' ? 'selected' : '' }}>Por vencer (3 días)</option>
                            <option value="without_due" {{ request('due_status') === 'without_due' ? 'selected' : '' }}>Sin vencimiento</option>
                        </select>
                    </div>

                    <!-- Solicitante -->
                    <div>
                        <label for="requesterFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Solicitante</label>
                        <input id="requesterFilterAdv" name="requester" value="{{ request('requester') }}" type="text" placeholder="Nombre o email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
                    </div>

                    <!-- Rango de fecha de la solicitud -->
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Rango de fecha de la solicitud</label>
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

                    <!-- Orden -->
                    <div>
                        <label for="sortByFilterAdv" class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
                        <select id="sortByFilterAdv" name="sort_by" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="recent" {{ request('sort_by', 'recent') === 'recent' ? 'selected' : '' }}>Fecha de solicitud más reciente</option>
                            <option value="oldest" {{ request('sort_by') === 'oldest' ? 'selected' : '' }}>Fecha de solicitud más antigua</option>
                            <option value="priority_high" {{ request('sort_by') === 'priority_high' ? 'selected' : '' }}>Prioridad alta a baja</option>
                            <option value="priority_low" {{ request('sort_by') === 'priority_low' ? 'selected' : '' }}>Prioridad baja a alta</option>
                            <option value="due_date" {{ request('sort_by') === 'due_date' ? 'selected' : '' }}>Vencimiento más cercano</option>
                            <option value="status_az" {{ request('sort_by') === 'status_az' ? 'selected' : '' }}>Estado A-Z</option>
                            <option value="status_za" {{ request('sort_by') === 'status_za' ? 'selected' : '' }}>Estado Z-A</option>
                        </select>
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
            <div class="sr-table-view rounded-lg border border-slate-200 overflow-hidden">
                <!-- Tabla Compacta -->
                <table class="sr-requests-table w-full table-fixed text-sm" aria-describedby="table-instructions">
                    <colgroup>
                        <col class="w-[14%]">
                        <col class="w-[32%]">
                        <col class="w-[9%]">
                        <col class="w-[11%]">
                        <col class="w-[16%]">
                        <col class="w-[9%]">
                        <col class="w-[9%]">
                    </colgroup>
                    <thead class="bg-slate-50 text-xs text-slate-600 uppercase">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Ticket</th>
                            <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Solicitud</th>
                            <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Prioridad</th>
                            <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Estado</th>
                            <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Solicitante</th>
                            <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Fecha solicitud</th>
                            <th class="px-3 py-2.5 text-right font-semibold tracking-wide">Acciones</th>
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
                        $isClosedCard = strtoupper((string) $request->status) === 'CERRADA';
                        
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
                        $hasDueDate = $request->hasRequestDueDate();
                        $isFinalForDueDate = in_array(strtoupper((string) $request->status), ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA'], true);
                        $dueDays = $request->daysUntilRequestDue();
                        $dueClasses = 'bg-slate-50 text-slate-700 border-slate-200';
                        $dueLabel = 'Sin vencimiento';

                        if ($hasDueDate) {
                            if ($isFinalForDueDate) {
                                $dueClasses = 'bg-slate-50 text-slate-600 border-slate-200';
                                $dueLabel = 'Registrado';
                            } elseif ($request->isRequestDueOverdue()) {
                                $dueClasses = 'bg-red-50 text-red-700 border-red-200';
                                $dueLabel = 'Vencida';
                            } elseif ($request->isRequestDueSoon()) {
                                $dueClasses = 'bg-amber-50 text-amber-700 border-amber-200';
                                $dueLabel = $dueDays === 0 ? 'Vence hoy' : 'Por vencer';
                            } else {
                                $dueClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                $dueLabel = 'En plazo';
                            }
                        }

                        $openStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'REABIERTO'];
                        $isOpenRequest = in_array(strtoupper((string) $request->status), $openStatuses, true);
                        $fallbackResponseMinutes = (int) ($request->sla->response_time_minutes ?? 0);
                        $responseStartAt = $request->accepted_at;
                        $responseDeadline = ($responseStartAt && $fallbackResponseMinutes > 0)
                            ? $responseStartAt->copy()->addMinutes($fallbackResponseMinutes)
                            : null;
                        $respondedAt = $request->responded_at;

                        $responseToneClasses = 'text-gray-700 bg-gray-100';
                        $responseLabel = 'Sin objetivo';
                        $responseDetail = 'Sin plazo';

                        $formatWindow = function (int $minutes): string {
                            $minutes = max(0, $minutes);
                            $hours = intdiv($minutes, 60);
                            $days = intdiv($hours, 24);
                            $remainingHours = $hours % 24;

                            if ($days > 0) {
                                return $days . 'd ' . $remainingHours . 'h';
                            }

                            return $hours . 'h';
                        };

                        if ($respondedAt) {
                            $responseToneClasses = 'text-emerald-700 bg-emerald-100';
                            $responseLabel = 'Respondida';
                            $responseDetail = $respondedAt->format('d/m H:i');
                        } elseif ($isOpenRequest && !$responseStartAt) {
                            $responseToneClasses = 'text-slate-700 bg-slate-100';
                            $responseLabel = 'Pendiente de aceptación';
                            $responseDetail = 'Aún no inicia';
                        } elseif ($isOpenRequest && $responseDeadline && $responseStartAt) {
                            $totalWindowMinutes = max(1, (int) $responseStartAt->diffInMinutes($responseDeadline));
                            $elapsedMinutes = max(0, (int) $responseStartAt->diffInMinutes(now()));
                            $remainingWindowMinutes = max(0, $totalWindowMinutes - $elapsedMinutes);
                            $responseProgress = min(100, (int) round(($elapsedMinutes / $totalWindowMinutes) * 100));

                            if ($responseProgress >= 90) {
                                $responseToneClasses = 'text-red-700 bg-red-100';
                                $responseLabel = 'Tiempo Crítico';
                            } elseif ($responseProgress >= 75) {
                                $responseToneClasses = 'text-amber-700 bg-amber-100';
                                $responseLabel = 'Tiempo en Riesgo';
                            } else {
                                $responseToneClasses = 'text-emerald-700 bg-emerald-100';
                                $responseLabel = 'En Tiempo';
                            }

                            $responseDetail = $formatWindow($remainingWindowMinutes);
                        }
                    @endphp
                    
                    <div class="{{ $isClosedCard ? 'bg-gray-50 rounded-lg shadow-sm border border-gray-300 hover:shadow-sm transition-shadow overflow-hidden' : 'bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow overflow-hidden' }}">
                        <!-- Header de la tarjeta -->
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex items-start justify-between mb-2">
                                <a href="{{ route('service-requests.show', $request) }}" 
                                   class="font-mono {{ $isClosedCard ? 'text-gray-700 hover:text-gray-900' : 'text-blue-600 hover:text-blue-800' }} hover:underline font-bold text-sm">
                                    {{ $request->ticket_number }}
                                </a>
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $isClosedCard ? 'bg-gray-200 text-gray-700' : ($criticalityColors[$request->criticality_level] ?? 'bg-gray-500 text-white') }}">
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
                                <i class="fas fa-cog {{ $isClosedCard ? 'text-gray-500' : 'text-gray-400' }} text-xs mt-0.5"></i>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-900 truncate">
                                        {{ $request->subService->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">
                                        @php
                                            $family = $request->subService?->service?->family;
                                            $familyName = $family?->name ?? '';
                                            $contractNumber = $family?->contract?->number;
                                            $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;
                                        @endphp
                                        {{ $familyLabel }}
                                    </div>
                                </div>
                            </div>

                            <!-- Vencimiento -->
                            <div class="flex items-start gap-2">
                                <i class="fas fa-calendar-check {{ $hasDueDate ? 'text-amber-500' : 'text-gray-400' }} text-xs mt-0.5"></i>
                                <div class="flex-1 min-w-0">
                                    @if($hasDueDate)
                                        <div class="inline-flex items-center gap-1 px-2 py-1 rounded-md border {{ $dueClasses }} text-[11px] font-semibold">
                                            <span>{{ $request->due_date->format('d/m/Y') }}</span>
                                            <span>{{ $dueLabel }}</span>
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-500">Sin vencimiento</div>
                                    @endif
                                </div>
                            </div>

                            
                            <!-- Solicitante -->
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 {{ $isClosedCard ? 'bg-gray-400 text-white' : 'bg-gradient-to-br ' . $colors[$colorIndex] . ' text-white' }} rounded-full flex items-center justify-center text-xs font-bold shadow-sm flex-shrink-0">
                                    {{ $initials }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-900 truncate">{{ $name }}</div>
                                    <div class="text-xs text-gray-500" title="Fecha solicitud: {{ $request->created_at->format('d/m/Y H:i') }}">
                                        {{ $request->created_at->format('d/m/Y') }} · {{ $request->created_at->locale('es')->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Estado -->
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <span class="px-3 py-1 text-xs font-semibold rounded border {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    {{ ucfirst(strtolower(str_replace('_', ' ', $request->status))) }}
                                </span>
                                <div class="flex gap-2">
                                    <a href="{{ route('service-requests.show', $request) }}" 
                                       class="{{ $isClosedCard ? 'text-gray-500 hover:text-gray-700' : 'text-blue-600 hover:text-blue-800' }} transition-colors" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('service-requests.edit', $request) }}" 
                                       class="{{ $isClosedCard ? 'text-gray-500 hover:text-gray-700' : 'text-yellow-600 hover:text-yellow-800' }} transition-colors" 
                                       title="Editar">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                </div>
                            </div>
                            @if(!$isClosedCard)
                                <div class="text-[11px] rounded-md px-2 py-1 {{ $responseToneClasses }}">
                                    <div class="font-semibold leading-tight break-words">{{ $responseLabel }}</div>
                                    <div class="font-medium leading-tight mt-0.5">{{ $responseDetail }}</div>
                                </div>
                            @endif
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
    .sr-requests-table th,
    .sr-requests-table td {
        padding: 0.625rem 0.75rem;
    }

    .sr-requests-table tr:focus {
        outline: 2px solid #3b82f6;
        background-color: #eff6ff;
    }

    .sr-requests-table tr:hover {
        background-color: #f9fafb;
    }

    /* Dropdown sugerencias */
    #requesterSuggestions li { padding:0.4rem 0.6rem; }
    #requesterSuggestions li:hover, #requesterSuggestions li[aria-selected='true'] { background:#eef2ff; }
</style>

<script>
function toggleView(view) {
    const tableView = document.querySelector('#tableContainer .sr-table-view');
    const cardsView = document.getElementById('cardsView');
    const tableBtn = document.getElementById('viewToggleTable');
    const cardsBtn = document.getElementById('viewToggleCards');
    if (!tableView || !cardsView || !tableBtn || !cardsBtn) return;
    
    if (view === 'table') {
        tableView.classList.remove('hidden');
        cardsView.classList.add('hidden');
        tableBtn.classList.add('bg-slate-900', 'text-white', 'shadow-sm');
        tableBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
        cardsBtn.classList.remove('bg-slate-900', 'text-white', 'shadow-sm');
        cardsBtn.classList.add('text-slate-600', 'hover:text-slate-900');
    } else {
        tableView.classList.add('hidden');
        cardsView.classList.remove('hidden');
        cardsBtn.classList.add('bg-slate-900', 'text-white', 'shadow-sm');
        cardsBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
        tableBtn.classList.remove('bg-slate-900', 'text-white', 'shadow-sm');
        tableBtn.classList.add('text-slate-600', 'hover:text-slate-900');
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
const SAVED_FILTERS_API = {
    list: '{{ route('service-requests.saved-filters.index') }}',
    store: '{{ route('service-requests.saved-filters.store') }}',
    destroyBase: '{{ route('service-requests.saved-filters.destroy', ['savedFilter' => '__ID__']) }}'
};
let savedPresetsCache = @json(($savedFilters ?? collect())->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'filters' => $item->filters])->values());

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
    if (field === 'all') {
        window.location.href = '{{ route("service-requests.index") }}';
        return;
    }
    const params = new URLSearchParams();
    params.set(field, value);
    window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
}

// === Sistema de Presets ===
function savePreset() {
    const name = prompt('Nombre del preset:');
    if (!name) return;
    
    const filters = gatherFilters();
    const token = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content');
    fetch(SAVED_FILTERS_API.store, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ name, filters })
    })
    .then(async (res) => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || 'Error al guardar preset');
        }
        return data;
    })
    .then((payload) => {
        if (payload.preset) {
            const existingIndex = savedPresetsCache.findIndex((p) => Number(p.id) === Number(payload.preset.id));
            if (existingIndex >= 0) {
                savedPresetsCache[existingIndex] = payload.preset;
            } else {
                savedPresetsCache.push(payload.preset);
            }
        }
        renderPresets();
        showToast(payload.message || 'Preset guardado exitosamente');
    })
    .catch((err) => {
        showToast(err.message || 'Error al guardar preset', 'error');
    });
}

function loadPresetById(id) {
    const preset = savedPresetsCache.find((item) => Number(item.id) === Number(id));
    if (!preset || !preset.filters) return;
        
        const params = new URLSearchParams();
        Object.keys(preset.filters).forEach(key => {
            if (preset.filters[key]) params.set(key, preset.filters[key]);
        });
        
        window.location.href = '{{ route("service-requests.index") }}?' + params.toString();
}

function deletePresetById(id) {
    const preset = savedPresetsCache.find((item) => Number(item.id) === Number(id));
    if (!preset) return;
    if (!confirm(`¿Eliminar preset \"${preset.name}\"?`)) return;
    
    const token = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content');
    const url = SAVED_FILTERS_API.destroyBase.replace('__ID__', String(id));
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async (res) => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || 'Error al eliminar preset');
        }
        return data;
    })
    .then((payload) => {
        savedPresetsCache = savedPresetsCache.filter((item) => Number(item.id) !== Number(id));
        renderPresets();
        showToast(payload.message || 'Preset eliminado');
    })
    .catch((err) => {
        showToast(err.message || 'Error al eliminar preset', 'error');
    });
}

function renderPresets() {
    const container = document.getElementById('presetsContainer');
    if (!container) return;
    
    const presets = Array.isArray(savedPresetsCache) ? savedPresetsCache : [];
    if (presets.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No hay presets guardados</p>';
        return;
    }

    container.innerHTML = presets.map(preset => 
        `<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <button type="button" onclick="loadPresetById(${Number(preset.id)})"
                        class="flex-1 text-left text-sm font-medium text-gray-700" title="Aplicar preset">
                    <i class="fas fa-star text-purple-500 mr-2"></i>${preset.name}
                </button>
                <button type="button" onclick="deletePresetById(${Number(preset.id)})"
                        class="text-red-500 hover:text-red-700 ml-2">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>`
    ).join('');
}

// === Aplicar Filtros ===
function gatherFilters() {
    return {
        search: document.getElementById('searchFilter')?.value || '',
        status: document.getElementById('statusFilterAdv')?.value || '',
        criticality: document.getElementById('criticalityFilterAdv')?.value || '',
        due_status: document.getElementById('dueStatusFilterAdv')?.value || '',
        service_id: document.getElementById('serviceFilterAdv')?.value || '',
        requester: document.getElementById('requesterFilterAdv')?.value || '',
        start_date: document.getElementById('startDateFilterAdv')?.value || '',
        end_date: document.getElementById('endDateFilterAdv')?.value || '',
        open: document.getElementById('openFilter')?.value || '',
        in_course: document.getElementById('inCourseFilter')?.value || '',
        in_process: document.getElementById('inProcessFilter')?.value || '',
        sort_by: document.getElementById('sortByFilterAdv')?.value || 'recent'
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
