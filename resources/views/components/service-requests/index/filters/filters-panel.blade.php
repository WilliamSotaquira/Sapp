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
            <x-service-requests.index.filters.status-filter />

            <!-- Filtro de Criticidad -->
            <x-service-requests.index.filters.criticality-filter />

            <!-- Botones de Acción -->
            <x-service-requests.index.filters.filter-actions />
        </div>
    </div>
</div>
