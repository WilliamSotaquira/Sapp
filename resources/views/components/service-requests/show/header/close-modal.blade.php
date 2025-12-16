<!-- Modal de Cerrar Solicitud - Sin validación de evidencias -->
<div id="close-modal-{{ $serviceRequest->id }}"
    class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="close-modal-title-{{ $serviceRequest->id }}"
    tabindex="-1">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full mr-3">
                    <i class="fas fa-lock text-purple-600 text-sm"></i>
                </div>
                <h3 id="close-modal-title-{{ $serviceRequest->id }}" class="text-lg font-medium text-gray-900">
                    Cerrar Solicitud
                </h3>
            </div>
            <button type="button"
                onclick="closeModal('close-modal-{{ $serviceRequest->id }}')"
                class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200"
                aria-label="Cerrar diálogo">
                ✕
            </button>
        </div>

        <!-- Información de la solicitud -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <span>Ticket: <strong>#{{ $serviceRequest->ticket_number }}</strong></span>
            </div>
            <div class="mt-2 text-sm text-blue-700">
                <p>Confirma el cierre definitivo de esta solicitud.</p>
            </div>
        </div>

        <!-- Detalles del cierre -->
        <div class="mb-4 bg-gray-50 rounded-md p-3">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Resumen del cierre:</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-gray-600">Ticket:</div>
                <div class="font-mono text-gray-900">{{ $serviceRequest->ticket_number }}</div>

                <div class="text-gray-600">Evidencias:</div>
                <div>
                    <span
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $serviceRequest->evidences->count() > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $serviceRequest->evidences->count() }} adjunta(s)
                    </span>
                </div>

                <div class="text-gray-600">Estado actual:</div>
                <div>
                    <span
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                        RESUELTA
                    </span>
                </div>

                <div class="text-gray-600">Nuevo estado:</div>
                <div>
                    <span
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                        CERRADA
                    </span>
                </div>
            </div>
        </div>

        <!-- En close-modal.blade.php - Campo corregido -->
        <div class="mb-4">
            <label for="resolution_description_close_{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                Descripción de Acciones Realizadas (Opcional)
            </label>
            <textarea name="resolution_description" id="resolution_description_close_{{ $serviceRequest->id }}" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-purple-500 focus:border-purple-500"
                placeholder="Describe las acciones realizadas para resolver esta solicitud (opcional)...">{{ old('resolution_description') }}</textarea>
        </div>

        <!-- Alerta de confirmación -->
        <div class="p-3 bg-purple-50 border border-purple-200 rounded-md">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-purple-500 mt-0.5 mr-2 flex-shrink-0"></i>
                <div>
                    <p class="text-sm font-medium text-purple-800">Acción Final</p>
                    <p class="text-xs text-purple-700 mt-1">
                        Al cerrar, la solicitud cambiará a estado <strong>CERRADA</strong> y no podrá ser modificada.
                    </p>
                </div>
            </div>
        </div>


        <!-- Formulario de cierre -->
        <form action="{{ route('service-requests.close', $serviceRequest) }}" method="POST" class="mt-4">
            @csrf
            @method('POST')

            <div class="flex justify-end space-x-3">
                <button type="button"
                    onclick="closeModal('close-modal-{{ $serviceRequest->id }}')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                    <i class="fas fa-lock mr-2"></i>
                    Confirmar Cierre
                </button>
            </div>
        </form>
    </div>
</div>
