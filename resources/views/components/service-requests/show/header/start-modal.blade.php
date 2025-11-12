<!-- Modal de Inicio de Servicio -->
<div id="start-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-cyan-100 rounded-full mr-3">
                    <i class="fas fa-play text-cyan-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                    Iniciar Servicio
                </h3>
            </div>
            <button type="button"
                    onclick="document.getElementById('start-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200">
                ✕
            </button>
        </div>

        <!-- Información del servicio -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <span>Preparación para iniciar servicio</span>
            </div>
            <div class="mt-2 text-sm text-blue-700">
                <p>Confirma que estás listo para comenzar el trabajo en esta solicitud.</p>
            </div>
        </div>

        <!-- Detalles del servicio -->
        <div class="mb-4 bg-gray-50 rounded-md p-3">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Detalles del servicio:</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-gray-600">Ticket:</div>
                <div class="font-mono text-gray-900">{{ $serviceRequest->ticket_number }}</div>

                <div class="text-gray-600">Técnico responsable:</div>
                <div>
                    @if($serviceRequest->assignee)
                        <span class="text-green-600 font-medium">{{ $serviceRequest->assignee->name }}</span>
                    @else
                        <span class="text-red-600">Sin asignar</span>
                    @endif
                </div>

                <div class="text-gray-600">Estado actual:</div>
                <div>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                        ACEPTADA
                    </span>
                </div>

                <div class="text-gray-600">Nuevo estado:</div>
                <div>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-cyan-100 text-cyan-800">
                        EN PROCESO
                    </span>
                </div>
            </div>
        </div>

        <!-- Información importante -->
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-md">
            <div class="flex items-start">
                <i class="fas fa-clock text-amber-500 mt-0.5 mr-2 flex-shrink-0"></i>
                <div>
                    <p class="text-sm font-medium text-amber-800">Registro de Tiempo</p>
                    <p class="text-xs text-amber-700 mt-1">
                        Al iniciar el servicio, el sistema comenzará a registrar el tiempo empleado en la resolución.
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulario de inicio -->
        <form action="{{ route('service-requests.start', $serviceRequest) }}"
              method="POST"
              class="mt-4">
            @csrf
            @method('PATCH')

            <div class="flex justify-end space-x-3">
                <button type="button"
                        onclick="document.getElementById('start-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 border border-transparent rounded-md hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-colors duration-200">
                    <i class="fas fa-play mr-2"></i>
                    Iniciar Servicio
                </button>
            </div>
        </form>
    </div>
</div>
