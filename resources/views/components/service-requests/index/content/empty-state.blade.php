<div class="text-center py-12">
    <div class="text-gray-400 mb-4">
        <i class="fas fa-inbox text-6xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-600 mb-2">No se encontraron solicitudes</h3>
<p class="text-gray-500 mb-4">No hay solicitudes que coincidan con los criterios actuales.</p>
<div class="flex flex-col sm:flex-row items-center justify-center gap-3">
    <a href="{{ route('service-requests.index') }}"
        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition duration-200 inline-flex items-center font-semibold">
        <i class="fas fa-times-circle mr-2"></i>Limpiar filtros
    </a>
    <a href="{{ route('service-requests.create') }}"
        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 inline-flex items-center font-semibold">
        <i class="fas fa-plus mr-2"></i>Crear la primera solicitud
    </a>
</div>
</div>
