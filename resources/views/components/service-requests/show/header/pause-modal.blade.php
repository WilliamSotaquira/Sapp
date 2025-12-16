<!-- Modal de Pausar Trabajo -->
<div id="pause-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
     role="dialog"
     aria-modal="true"
     aria-hidden="true"
     aria-labelledby="pause-modal-title-{{ $serviceRequest->id }}"
     tabindex="-1">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-yellow-100 rounded-full mr-3">
                    <i class="fas fa-pause text-yellow-600 text-sm"></i>
                </div>
                <h3 id="pause-modal-title-{{ $serviceRequest->id }}" class="text-lg font-medium text-gray-900">
                    Pausar Trabajo
                </h3>
            </div>
            <button type="button"
                    onclick="closeModal('pause-modal-{{ $serviceRequest->id }}')"
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
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <ul class="text-sm text-red-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('service-requests.pause', $serviceRequest) }}" method="POST">
            @csrf
            @method('POST')

            <div class="space-y-4">
                <div>
                    <label for="pause_reason" class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo de la Pausa *
                    </label>
                    <textarea name="pause_reason"
                            id="pause_reason"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-yellow-500 focus:border-yellow-500"
                            placeholder="Describe detalladamente por qué se pausa este trabajo..."
                            required
                            minlength="10">{{ old('pause_reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres. Esta razón quedará registrada en el historial.</p>
                </div>

                <!-- Alerta informativa -->
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <div class="flex items-start">
                        <i class="fas fa-clock text-yellow-500 mt-0.5 mr-2 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Registro de Tiempo</p>
                            <p class="text-xs text-yellow-700 mt-1">
                                Al pausar el trabajo, se detendrá el registro de tiempo hasta que se reanude.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                        onclick="closeModal('pause-modal-{{ $serviceRequest->id }}')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200">
                    <i class="fas fa-pause mr-2"></i>
                    Confirmar Pausa
                </button>
            </div>
        </form>
    </div>
</div>
