<!-- Modal de Reabrir Solicitud -->
<div id="reopen-modal-{{ $serviceRequest->id }}"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 bg-orange-100 rounded-full mr-3">
                    <i class="fas fa-undo text-orange-600 text-sm"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                    Reabrir Solicitud
                </h3>
            </div>
            <button type="button"
                    onclick="document.getElementById('reopen-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
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
            <div class="mt-2 text-sm text-blue-700">
                <p>Esta solicitud será reabierta para realizar ajustes o correcciones.</p>
            </div>
        </div>

        <!-- Detalles del estado actual -->
        <div class="mb-4 bg-gray-50 rounded-md p-3">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Información actual:</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-gray-600">Estado actual:</div>
                <div>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                        RESUELTA
                    </span>
                </div>

                <div class="text-gray-600">Nuevo estado:</div>
                <div>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">
                        EN PROCESO
                    </span>
                </div>

                @if($serviceRequest->assignee)
                <div class="text-gray-600">Técnico asignado:</div>
                <div class="text-gray-900">
                    {{ $serviceRequest->assignee->name }}
                </div>
                @endif
            </div>
        </div>

        <!-- Campo para motivo de reapertura -->
        <form action="{{ route('service-requests.reopen', $serviceRequest) }}" method="POST">
            @csrf
            @method('POST')

            <div class="space-y-4">
                <div>
                    <label for="reopen_reason" class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo de la Reapertura *
                    </label>
                    <textarea name="reopen_reason"
                            id="reopen_reason"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-orange-500 focus:border-orange-500"
                            placeholder="Explica por qué necesitas reabrir esta solicitud..."
                            required
                            minlength="10">{{ old('reopen_reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres. Describe el motivo de la reapertura.</p>
                </div>

                <!-- Alerta importante -->
                <div class="p-3 bg-orange-50 border border-orange-200 rounded-md">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-500 mt-0.5 mr-2 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-medium text-orange-800">Cambio de Estado</p>
                            <p class="text-xs text-orange-700 mt-1">
                                Al reabrir, la solicitud volverá a estado <strong>EN PROCESO</strong> y el técnico podrá realizar ajustes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button"
                        onclick="document.getElementById('reopen-modal-{{ $serviceRequest->id }}').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200">
                    <i class="fas fa-undo mr-2"></i>
                    Confirmar Reapertura
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Script para mejorar experiencia -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('[id^="reopen-modal-"]');
            modals.forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });

    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (e.target.matches('[id^="reopen-modal-"]')) {
            e.target.classList.add('hidden');
        }
    });
});
</script>
