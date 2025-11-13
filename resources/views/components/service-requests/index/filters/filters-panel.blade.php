<div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-800 flex items-center">
            <i class="fas fa-filter text-blue-500 mr-2"></i>
            Filtros
        </h3>
    </div>

    <form id="filtersForm" method="GET" action="{{ route('service-requests.index') }}">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Filtro de Estado -->
            <div>
                <label for="status" class="block text-xs font-medium text-gray-700 mb-2">
                    <i class="fas fa-tag mr-2"></i>Estado
                </label>
                <select id="status"
                        name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                <label for="criticality" class="block text-xs font-medium text-gray-700 mb-2">
                    <i class="fas fa-flag mr-2"></i>Prioridad
                </label>
                <select id="criticality"
                        name="criticality"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas las prioridades</option>
                    <option value="BAJA" {{ request('criticality') == 'BAJA' ? 'selected' : '' }}>Baja</option>
                    <option value="MEDIA" {{ request('criticality') == 'MEDIA' ? 'selected' : '' }}>Media</option>
                    <option value="ALTA" {{ request('criticality') == 'ALTA' ? 'selected' : '' }}>Alta</option>
                    <option value="CRITICA" {{ request('criticality') == 'CRITICA' ? 'selected' : '' }}>Crítica</option>
                </select>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-end space-x-3">
                <button type="button"
                        id="clearFilters"
                        class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition duration-200 font-medium text-sm">
                    <i class="fas fa-times mr-2"></i>Limpiar
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200 font-medium text-sm">
                    <i class="fas fa-check mr-2"></i>Aplicar
                </button>
            </div>
        </div>

        <!-- Campo oculto para mantener la búsqueda si existe -->
        @if(request('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filtersForm');
    const clearBtn = document.getElementById('clearFilters');

    // Limpiar filtros
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            // Redirigir a la URL sin parámetros de filtro
            const url = new URL(window.location.href);
            url.searchParams.delete('status');
            url.searchParams.delete('criticality');
            // Mantener solo el parámetro de búsqueda si existe
            if (!url.searchParams.get('search')) {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        });
    }

    // Aplicar filtros automáticamente al cambiar select (opcional)
    const selects = form.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            form.submit();
        });
    });
});
</script>
@endpush
