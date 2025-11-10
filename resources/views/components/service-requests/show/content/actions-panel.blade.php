@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-purple-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-play-circle text-purple-600 mr-3"></i>
            Acciones y Operaciones
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Ver en Sistema -->
            <a href="{{ route('service-requests.show', $serviceRequest) }}"
               class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-150 text-center">
                <i class="fas fa-eye text-blue-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Ver Detalles</span>
                <span class="text-sm text-gray-500 mt-1">Información completa</span>
            </a>

            <!-- Editar -->
            @if(in_array($serviceRequest->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA']))
            <a href="{{ route('service-requests.edit', $serviceRequest) }}"
               class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition duration-150 text-center">
                <i class="fas fa-edit text-green-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Editar</span>
                <span class="text-sm text-gray-500 mt-1">Modificar solicitud</span>
            </a>
            @else
            <div class="flex flex-col items-center p-4 bg-gray-100 rounded-lg text-center opacity-50">
                <i class="fas fa-edit text-gray-400 text-xl mb-2"></i>
                <span class="font-medium text-gray-500">Editar</span>
                <span class="text-sm text-gray-400 mt-1">No disponible</span>
            </div>
            @endif

            <!-- Cambiar Estado -->
            <button type="button"
                    class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition duration-150 text-center">
                <i class="fas fa-sync-alt text-orange-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Cambiar Estado</span>
                <span class="text-sm text-gray-500 mt-1">Actualizar estado</span>
            </button>

            <!-- Descargar Reporte -->
            <button type="button"
                    class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition duration-150 text-center">
                <i class="fas fa-download text-indigo-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Descargar</span>
                <span class="text-sm text-gray-500 mt-1">Reporte PDF</span>
            </button>

            <!-- Compartir -->
            <button type="button"
                    class="flex flex-col items-center p-4 bg-pink-50 rounded-lg hover:bg-pink-100 transition duration-150 text-center">
                <i class="fas fa-share-alt text-pink-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Compartir</span>
                <span class="text-sm text-gray-500 mt-1">Enlace de acceso</span>
            </button>

            <!-- Historial -->
            <a href="#history-section"
               class="flex flex-col items-center p-4 bg-teal-50 rounded-lg hover:bg-teal-100 transition duration-150 text-center">
                <i class="fas fa-history text-teal-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Historial</span>
                <span class="text-sm text-gray-500 mt-1">Ver timeline</span>
            </a>

            <!-- Evidencias -->
            <a href="#evidences-section"
               class="flex flex-col items-center p-4 bg-amber-50 rounded-lg hover:bg-amber-100 transition duration-150 text-center">
                <i class="fas fa-images text-amber-600 text-xl mb-2"></i>
                <span class="font-medium text-gray-900">Evidencias</span>
                <span class="text-sm text-gray-500 mt-1">Ver archivos</span>
            </a>

            <!-- Eliminar -->
            @if(in_array($serviceRequest->status, ['PENDIENTE', 'CANCELADA']))
            <form action="{{ route('service-requests.destroy', $serviceRequest) }}"
                  method="POST"
                  class="flex flex-col items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition duration-150 text-center"
                  onsubmit="return confirm('¿Está seguro de que desea eliminar esta solicitud?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex flex-col items-center">
                    <i class="fas fa-trash text-red-600 text-xl mb-2"></i>
                    <span class="font-medium text-gray-900">Eliminar</span>
                    <span class="text-sm text-gray-500 mt-1">Remover solicitud</span>
                </button>
            </form>
            @else
            <div class="flex flex-col items-center p-4 bg-gray-100 rounded-lg text-center opacity-50">
                <i class="fas fa-trash text-gray-400 text-xl mb-2"></i>
                <span class="font-medium text-gray-500">Eliminar</span>
                <span class="text-sm text-gray-400 mt-1">No disponible</span>
            </div>
            @endif
        </div>
    </div>
</div>
