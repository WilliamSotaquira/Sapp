<!-- Modal de Cierre por Vencimiento -->
<div id="vencimiento-modal-{{ $serviceRequest->id }}"
    class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-red-100 rounded-full mr-3">
                    <i class="fas fa-clock text-red-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                    Cerrar por Vencimiento
                </h3>
            </div>
            <button type="button"
                onclick="document.getElementById('vencimiento-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-500 text-xl transition-colors duration-200">
                ✕
            </button>
        </div>

        <!-- Información de la solicitud -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <span>Ticket: <strong>#{{ $serviceRequest->ticket_number }}</strong></span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <ul class="text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('service-requests.close', $serviceRequest) }}" method="POST">
            @csrf
            @method('POST')

            <div class="space-y-4">
                <!-- Campo de justificación -->
                <div>
                    <label for="closure_reason" class="block text-sm font-medium text-gray-700 mb-1">
                        Justificación del Cierre por Vencimiento *
                    </label>
                    <textarea name="closure_reason" id="closure_reason" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-red-500 focus:border-red-500 transition-colors duration-200"
                        placeholder="Describe detalladamente el motivo del cierre por vencimiento..." required minlength="10">{{ old('closure_reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        Mínimo 10 caracteres. Esta justificación quedará registrada en el historial.
                    </p>
                </div>

                <!-- Alerta de advertencia -->
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Acción Importante</p>
                            <p class="text-xs text-yellow-700 mt-1">
                                Al cerrar por vencimiento, la solicitud cambiará a estado <strong>CERRADA</strong> y no
                                podrá ser reabierta.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Estado actual -->
                <div class="mb-3 p-3 bg-orange-50 border border-orange-200 rounded-md">
                    <div class="flex items-center text-sm text-orange-800">
                        <i class="fas fa-exclamation-circle mr-2 text-orange-500"></i>
                        <span>
                            Estado actual: <strong>{{ $serviceRequest->status }}</strong> -
                            @if ($serviceRequest->status === 'PAUSADA')
                                Cerrando solicitud pausada por vencimiento
                            @else
                                Cerrando solicitud resuelta
                            @endif
                        </span>
                    </div>
                </div>

            </div>

            <!-- Botones de acción -->
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                    onclick="document.getElementById('vencimiento-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                    <i class="fas fa-lock mr-2"></i>
                    Confirmar Cierre
                </button>
            </div>


        </form>
    </div>
</div>
