{{-- resources/views/components/service-requests/list/filters.blade.php --}}
@props(['statuses' => [], 'criticalityLevels' => [], 'services' => [], 'technicians' => [], 'filters' => []])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <!-- Header de Filtros -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-filter text-blue-600 mr-2"></i>
                Filtros y Búsqueda
            </h3>
            <div class="flex items-center space-x-3">
                @if($this->hasActiveFilters($filters))
                <button
                    onclick="resetFilters()"
                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                    <i class="fas fa-redo-alt mr-1"></i>
                    Limpiar Filtros
                </button>
                @endif
                <span class="text-sm text-gray-500">
                    {{ $this->getFilteredCount($filters) }} resultados
                </span>
            </div>
        </div>
    </div>

    <!-- Formulario de Filtros -->
    <form id="filterForm" action="{{ route('service-requests.index') }}" method="GET" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Búsqueda General -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-1"></i>Búsqueda
                </label>
                <input
                    type="text"
                    name="search"
                    id="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar por título, descripción..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            <!-- Estado -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-circle mr-1"></i>Estado
                </label>
                <select
                    name="status"
                    id="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">Todos los estados</option>
                    @foreach($statuses as $status)
                    <option value="{{ $status }}"
                        {{ request('status') == $status ? 'selected' : '' }}>
                        {{ $status }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Nivel de Criticidad -->
            <div>
                <label for="criticality_level" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-flag mr-1"></i>Criticidad
                </label>
                <select
                    name="criticality_level"
                    id="criticality_level"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">Todos los niveles</option>
                    @foreach($criticalityLevels as $level)
                    <option value="{{ $level }}"
                        {{ request('criticality_level') == $level ? 'selected' : '' }}>
                        {{ $level }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Servicio -->
            <div>
                <label for="service_id" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-cogs mr-1"></i>Servicio
                </label>
                <select
                    name="service_id"
                    id="service_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">Todos los servicios</option>
                    @foreach($services as $service)
                    <option value="{{ $service->id }}"
                        {{ request('service_id') == $service->id ? 'selected' : '' }}>
                        {{ $service->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Técnico Asignado -->
            <div>
                <label for="assignee_id" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-1"></i>Técnico
                </label>
                <select
                    name="assignee_id"
                    id="assignee_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">Todos los técnicos</option>
                    <option value="unassigned" {{ request('assignee_id') == 'unassigned' ? 'selected' : '' }}>
                        Sin asignar
                    </option>
                    @foreach($technicians as $technician)
                    <option value="{{ $technician->id }}"
                        {{ request('assignee_id') == $technician->id ? 'selected' : '' }}>
                        {{ $technician->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha Desde -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Desde
                </label>
                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Hasta
                </label>
                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            <!-- Ordenamiento -->
            <div>
                <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-sort mr-1"></i>Ordenar por
                </label>
                <select
                    name="sort_by"
                    id="sort_by"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="created_at" {{ request('sort_by', 'created_at') == 'created_at' ? 'selected' : '' }}>
                        Fecha de creación
                    </option>
                    <option value="updated_at" {{ request('sort_by') == 'updated_at' ? 'selected' : '' }}>
                        Fecha de actualización
                    </option>
                    <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>
                        Título
                    </option>
                    <option value="criticality_level" {{ request('sort_by') == 'criticality_level' ? 'selected' : '' }}>
                        Nivel de criticidad
                    </option>
                </select>
            </div>

            <!-- Dirección del orden -->
            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-arrow-down mr-1"></i>Dirección
                </label>
                <select
                    name="sort_order"
                    id="sort_order"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="desc" {{ request('sort_order', 'desc') == 'desc' ? 'selected' : '' }}>
                        Descendente
                    </option>
                    <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>
                        Ascendente
                    </option>
                </select>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
            <div class="flex space-x-3">
                <!-- Filtros Rápidos -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Rápidos:</span>
                    <a href="{{ route('service-requests.index', ['status' => 'PENDIENTE']) }}"
                        class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200 transition">
                        Pendientes
                    </a>
                    <a href="{{ route('service-requests.index', ['criticality_level' => 'ALTA']) }}"
                        class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 transition">
                        Alta Criticidad
                    </a>
                    <a href="{{ route('service-requests.index', ['assignee_id' => 'unassigned']) }}"
                        class="text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded hover:bg-gray-200 transition">
                        Sin Asignar
                    </a>
                </div>
            </div>

            <div class="flex space-x-3">
                <!-- Botón Limpiar -->
                <button
                    type="button"
                    onclick="resetFilters()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Limpiar
                </button>

                <!-- Botón Aplicar -->
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                    <i class="fas fa-filter mr-2"></i>
                    Aplicar Filtros
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Etiquetas de Filtros Activos -->
@if($this->hasActiveFilters($filters))
<div class="mb-4">
    <div class="flex flex-wrap gap-2">
        <span class="text-sm text-gray-600">Filtros activos:</span>
        @foreach($this->getActiveFilterLabels($filters) as $filter)
        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
            {{ $filter['label'] }}: {{ $filter['value'] }}
            <a href="{{ $filter['remove_url'] }}" class="ml-2 text-blue-600 hover:text-blue-800">
                <i class="fas fa-times"></i>
            </a>
        </span>
        @endforeach
    </div>
</div>
@endif

<script>
    function resetFilters() {
        // Resetear todos los campos del formulario
        document.getElementById('filterForm').reset();

        // Enviar el formulario sin parámetros
        window.location.href = "{{ route('service-requests.index') }}";
    }

    // Auto-submit al cambiar algunos filtros
    document.addEventListener('DOMContentLoaded', function() {
        const autoSubmitElements = ['status', 'criticality_level', 'service_id', 'assignee_id', 'sort_by', 'sort_order'];

        autoSubmitElements.forEach(function(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.addEventListener('change', function() {
                    document.getElementById('filterForm').submit();
                });
            }
        });
    });
</script>

<style>
    /* Estilos para mejorar la experiencia de filtrado */
    select:focus,
    input:focus {
        outline: none;
        ring: 2px;
    }

    .hover\:bg-gray-50:hover {
        background-color: #f9fafb;
    }

    .transition {
        transition: all 0.2s ease-in-out;
    }
</style>
