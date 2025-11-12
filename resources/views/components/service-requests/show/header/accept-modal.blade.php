<!-- Modal de Aceptación de Solicitud -->
<div id="accept-modal-{{ $serviceRequest->id }}"
    class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-emerald-100 rounded-full mr-3">
                    <i class="fas fa-handshake text-emerald-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                    Aceptar Solicitud
                </h3>
            </div>
            <button type="button"
                onclick="document.getElementById('accept-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200">
                ✕
            </button>
        </div>

        <!-- Información de verificación -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <span>Verificación de información</span>
            </div>
            <div class="mt-2 text-sm text-blue-700">
                <p>Confirma que la información de la solicitud es correcta y completa antes de aceptar.</p>
            </div>
        </div>

        <!-- Detalles de la solicitud -->
        <div class="mb-4 bg-gray-50 rounded-md p-3">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Resumen de la solicitud:</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-gray-600">Ticket:</div>
                <div class="font-mono text-gray-900">{{ $serviceRequest->ticket_number }}</div>

                <div class="text-gray-600">Técnico asignado:</div>
                <div>
                    @if ($serviceRequest->assignee)
                        <span class="text-green-600 font-medium">{{ $serviceRequest->assignee->name }}</span>
                    @else
                        <span class="text-red-600">Sin asignar</span>
                    @endif
                </div>

                <div class="text-gray-600">Criticidad:</div>
                <div>
                    <span
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
        @if ($serviceRequest->criticality_level === 'ALTA') bg-red-100 text-red-800
        @elseif($serviceRequest->criticality_level === 'MEDIA') bg-yellow-100 text-yellow-800
        @elseif($serviceRequest->criticality_level === 'BAJA') bg-green-100 text-green-800
        @else bg-gray-100 text-gray-800 @endif">
                        {{ $serviceRequest->criticality_level }}
                    </span>
                </div>

                <div class="text-gray-600">Estado actual:</div>
                <div>
                    <span
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                        PENDIENTE
                    </span>
                </div>
            </div>
        </div>

        <!-- Alerta de confirmación -->
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-2 flex-shrink-0"></i>
                <div>
                    <p class="text-sm font-medium text-emerald-800">Confirmar Aceptación</p>
                    <p class="text-xs text-emerald-700 mt-1">
                        Al aceptar, la solicitud cambiará a estado <strong>ACEPTADA</strong> y se iniciará el proceso de
                        servicio.
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulario de aceptación -->
        <form action="{{ route('service-requests.accept', $serviceRequest) }}" method="POST" class="mt-4">
            @csrf
            @method('PATCH')

            <div class="flex justify-end space-x-3">
                <button type="button"
                    onclick="document.getElementById('accept-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200">
                    <i class="fas fa-handshake mr-2"></i>
                    Confirmar Aceptación
                </button>
            </div>
        </form>
    </div>
</div>
